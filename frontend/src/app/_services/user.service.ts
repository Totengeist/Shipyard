import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Router } from '@angular/router';
import { Observable, interval, Subscription } from 'rxjs';
import { environment } from '../../environments/environment';
import { AuthService } from './auth.service';
import { TokenStorageService } from './token-storage.service';

const httpOptions = {
  headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', Accept: '*/*' })
};

@Injectable({
  providedIn: 'root'
})
export class UserService {
  roles: string[] = [];
  isLoginFailed = false;
  errorMessage = '';
  showDashboard = false;
  username = '';
  activeLogin: Subscription = new Subscription();

  constructor(
    private authService: AuthService,
    private tokenStorageService: TokenStorageService,
    private router: Router,
    private http: HttpClient
  ) { }

  initializeUserInfo(): void {
    if (this.isLoggedIn()) {
      const user = this.tokenStorageService.getUser();
      this.roles = user.roles;
      this.showDashboard = (this.roles.length > 0);
      this.username = user.name;

      this.activeLogin.unsubscribe();
      this.activeLogin = interval(30000).subscribe(() => { this.refresh(); console.log('Session check'); });
    } else {
      this.roles = [];
      this.showDashboard = false;
      this.username = '';
    }
  }

  logout(): void {
    this.authService.logout().subscribe(
      () => {
        this.tokenStorageService.signOut();
        this.initializeUserInfo();
        this.activeLogin.unsubscribe();
        this.router.navigate(['/home']);
      },
      err => {
        console.log( err.message );
      }
    );
  }

  removeSteam(): Observable<any> {
    return this.http.post(environment.standardUrl + 'steam/remove', httpOptions);
  }

  removeDiscord(): Observable<any> {
    return this.http.post(environment.standardUrl + 'discord/remove', httpOptions);
  }

  saveUserData(data: any): void {
    const roles: string[] = [];
    data.roles?.forEach((element: any) => {
      roles.push(element.label);
    });
    const permissions: string[] = [];
    if (roles.length > 0) {
      data.roles[0].permissions.forEach((element: any) => {
        permissions.push(element.label);
      });
    }
    const userData: object = {
      name: data.name,
      ref: data.ref,
      email: data.email,
      roles,
      permissions,
      hasSteamLogin: data.steam,
      hasDiscordLogin: data.discord
    };
    this.tokenStorageService.saveUser(userData);
  }

  refresh(): void {
    this.getUserBoard().subscribe(
      data => {
        this.saveUserData(data);
        this.initializeUserInfo();
      },
      err => {
        if ( err.status >= 400 && err.status < 500  && this.isLoggedIn()) {
          this.activeLogin.unsubscribe();
          this.tokenStorageService.signOut();
          this.initializeUserInfo();
          this.router.navigate(['/home']);
        }
      }
    );
  }

  login(username: string, password: string): void {
    this.authService.login(username, password).subscribe(
      data => {
        this.saveUserData(data);
        this.initializeUserInfo();
        this.isLoginFailed = false;
        this.router.navigate(['/home']);
      },
      err => {
        this.errorMessage = err.error.message;
        this.isLoginFailed = true;
        const login = document.getElementById('login-button') as HTMLButtonElement;
        if ( login !== null ) {
          login.disabled = false;
        }
      }
    );
  }

  edit(user: {username: string|null, email: string|null, password: string|null, password_confirmation: string|null, ref: string} ): void {
    console.log(user);
    this.authService.edit(user.ref, user.username, user.email, user.password, user.password_confirmation).subscribe(
      data => {
        this.saveUserData(data);
        this.initializeUserInfo();
        this.isLoginFailed = false;
        this.router.navigate(['/home']);
      },
      err => {
        this.errorMessage = err.error.message;
        this.isLoginFailed = true;
        const login = document.getElementById('login-button') as HTMLButtonElement;
        if ( login !== null ) {
          login.disabled = false;
        }
      }
    );
  }

  isLoggedIn(): boolean {
    return !!this.tokenStorageService.getUser();
  }

  getUserBoard(): Observable<any> {
    return this.authService.me();
  }
}
