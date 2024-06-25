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
  isLoggedIn: boolean = false;
  isLoginFailed: boolean = false;
  errorMessage:string = '';
  showDashboard: boolean = false;
  username: string = "";

  constructor(private authService: AuthService, private tokenStorageService: TokenStorageService, private router: Router, private http: HttpClient) { }

  initializeUserInfo(): void {
    this.isLoggedIn = !!this.tokenStorageService.getUser();

    if (this.isLoggedIn) {
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
    data.roles.forEach((element: any) => {
        roles.push(element.label);
    })
    var permissions: string[] = [];
    if (roles.length > 0) {
        data.roles[0].permissions.forEach((element: any) => {
            permissions.push(element.label);
        });
    }
    var userData: object = { "name": data.name, "ref": data.ref, "email": data.email, "roles": roles, "permissions": permissions };
    this.tokenStorageService.saveUser(userData);
  }

  refresh() {
    this.getUserBoard().subscribe(
      data => {
        this.saveUserData(data);
        this.initializeUserInfo();
      },
      err => {
        this.errorMessage = err.error.message;
        this.isLoginFailed = true;
      }
    );
  }

  login(username: string, password: string) {
    this.authService.login(username, password).subscribe(
      data => {
        this.saveUserData(data);

        this.isLoginFailed = false;
        this.initializeUserInfo();
        if (this.roles.length > 0) {
            this.router.navigate(['/admin/dashboard']);
        } else {
            this.router.navigate(['/home']);
        }
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

  getUserBoard(): Observable<any> {
    return this.http.get(environment.apiUrl + 'me', { responseType: 'text' });
  }
}