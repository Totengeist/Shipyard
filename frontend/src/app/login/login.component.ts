import { Component, OnInit } from '@angular/core';
import { AuthService } from '../_services/auth.service';
import { TokenStorageService } from '../_services/token-storage.service';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css']
})
export class LoginComponent implements OnInit {
  form: any = {
    username: null,
    password: null
  };
  isLoggedIn = false;
  isLoginFailed = false;
  errorMessage = '';
  name: string = '';

  constructor(private authService: AuthService, private tokenStorage: TokenStorageService) { }

  ngOnInit(): void {
    if (this.tokenStorage.getToken()) {
      this.isLoggedIn = true;
      this.name = this.tokenStorage.getUser().name;
    }
  }

  onSubmit(): void {
    const { username, password } = this.form;

    this.authService.login(username, password).subscribe(
      data => {
        var roles: string[] = [];
        data.user.roles.forEach((element: any) => {
            roles.push(element.label);
        })
        var permissions: string[] = [];
        data.user.roles[0].permissions.forEach((element: any) => {
            permissions.push(element.label);
        })
        var userData: object = { "name": data.user.name, "email": data.user.email, "roles": roles, "permissions": permissions };
        this.tokenStorage.saveToken(data.access_token);
        this.tokenStorage.saveUser(userData);

        this.isLoginFailed = false;
        this.isLoggedIn = true;
        this.name = this.tokenStorage.getUser().name;
        this.reloadPage();
      },
      err => {
        this.errorMessage = err.error.message;
        this.isLoginFailed = true;
      }
    );
  }

  reloadPage(): void {
    window.location.reload();
  }
}