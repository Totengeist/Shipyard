import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { AuthService } from './auth.service';
import { TokenStorageService } from './token-storage.service';

@Injectable({
  providedIn: 'root'
})
export class UserService {
  roles: string[] = [];
  isLoginFailed: boolean = false;
  errorMessage:string = '';
  showDashboard: boolean = false;
  username: string = "";

  constructor(private authService: AuthService, private tokenStorageService: TokenStorageService, private router: Router, private http: HttpClient) { }

  initializeUserInfo(): void {
    if (this.isLoggedIn()) {
      const user = this.tokenStorageService.getUser();
      this.roles = user.roles;
      this.showDashboard = (this.roles.length > 0);
      this.username = user.name;
    } else {
      this.roles = [];
      this.showDashboard = false;
      this.username = "";
    }
  }

  logout(): void {
    this.authService.logout().subscribe(
      data => {
        this.tokenStorageService.signOut();
        this.initializeUserInfo();
        this.router.navigate(['/home']);
      },
      err => {
        alert( err.message );
      }
    );
  }
  
  saveUserData(data: any) {
    var roles: string[] = [];
    data.roles?.forEach((element: any) => {
        roles.push(element.label);
    })
    var permissions: string[] = [];
    if (roles.length > 0) {
        data.roles[0].permissions.forEach((element: any) => {
            permissions.push(element.label);
        });
    }
    var userData: object = { "name": data.name, "ref": data.ref, "email": data.email, "roles": roles, "permissions": permissions, "hasSteamLogin": data.steam };
    this.tokenStorageService.saveUser(userData);
  }

  refresh() {
    this.getUserBoard().subscribe(
      data => {
        this.saveUserData(data);
        this.initializeUserInfo();
      },
      err => {
        if( err.status >= 400 && err.status < 500  && this.isLoggedIn()) {
          this.tokenStorageService.signOut();
          this.initializeUserInfo();
          this.router.navigate(['/home']);
        }
      }
    );
  }

  login(username: string, password: string) {
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
        let login = document.getElementById("login-button") as HTMLButtonElement;
        if( login !== null ) {
            login.disabled = true;
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