import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Router } from '@angular/router';
import { Observable, interval, Subscription } from 'rxjs';
import { environment } from '../../environments/environment';
import { PermissionInterface } from '../_types/permission.interface';
import { RoleInterface } from '../_types/role.interface';
import { AuthService } from './auth.service';
import { TokenStorageService } from './token-storage.service';

const httpOptions = {
  headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', Accept: '*/*' })
};

@Injectable({
  providedIn: 'root'
})
export class UserService {
  roles: RoleInterface[] = [];
  permissions: PermissionInterface[] = [];
  isLoginFailed = false;
  errorMessage = '';
  showDashboard = false;
  ref = '';
  username = '';
  email = '';
  activeLogin: Subscription = new Subscription();
  hasSteamLogin = false;
  hasDiscordLogin = false;

  constructor(
    private authService: AuthService,
    private tokenStorageService: TokenStorageService,
    private router: Router,
    private http: HttpClient
  ) { }

  initializeUserInfo(): void {
    const user = this.tokenStorageService.getUser();
    if (user) {
      this.roles = user.roles ?? [];
      this.permissions = this.getPermissions(this.roles);
      this.showDashboard = (this.roles.length > 0);
      this.ref = user.ref;
      this.username = user.name??'';
      this.email = user.email;

      this.hasSteamLogin = user.hasSteamLogin ?? false;
      this.hasDiscordLogin = user.hasDiscordLogin ?? false;

      this.activeLogin.unsubscribe();
      this.activeLogin = interval(300000).subscribe(() => { this.refresh(); });
    } else {
      this.roles = [];
      this.permissions = [];
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

  can(permission: string): boolean {
    return this.permissions.some(item => this.hasPermission(item, permission));
  }

  hasPermission(perm: PermissionInterface, check: string) {
    return perm.label === check;
  }

  removeSteam(): Observable<any> {
    return this.http.post(environment.standardUrl + 'steam/remove', httpOptions);
  }

  removeDiscord(): Observable<any> {
    return this.http.post(environment.standardUrl + 'discord/remove', httpOptions);
  }

  getPermissions(roles: RoleInterface[]): PermissionInterface[] {
    const perms: PermissionInterface[] = [];
    roles.forEach(function(role) {
      if( role.permissions ) {
        role.permissions.forEach(function(permission) {
          if (!perms.includes(permission)) {
            perms.push(permission);
          }
        });
      }
    });

    return perms;
  }

  refresh(): void {
    this.authService.me().subscribe(
      data => {
        this.tokenStorageService.saveUser(data);
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
        this.tokenStorageService.saveUser(data);
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

  edit(user: {username: string|null, email: string|null, password: string|undefined, password_confirmation: string|undefined, ref: string} ): void {
    this.authService.edit(user.ref, user.username, user.email, user.password, user.password_confirmation).subscribe(
      data => {
        this.tokenStorageService.saveUser(data);
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
}
