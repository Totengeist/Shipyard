import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { UserService } from '../_services/user.service';

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
  user: UserService = {} as UserService;

  constructor(private route: ActivatedRoute, private userService: UserService) { }

  ngOnInit(): void {
    this.user = this.userService;
    const queryError: string|null = this.route.snapshot.queryParamMap.get('error');
    if ( queryError !== null ) {
        this.user.isLoginFailed = true;
        if ( queryError === 'steam_not_linked' ) {
            this.user.errorMessage = 'This Steam account is not linked to a user.';
        }
    }
  }

  onSubmit(): void {
    const { username, password } = this.form;
    const login = document.getElementById('login-button') as HTMLButtonElement;
    if ( login !== null ) {
        login.disabled = true;
    }
    this.userService.login(username, password);
  }
}
