import { NgIf } from '@angular/common';
import { Component, OnInit } from '@angular/core'; // eslint-disable-line import/named
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { environment } from '../../environments/environment';
import { UserService } from '../_services/user.service';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css'],
  standalone: true,
  imports: [NgIf, FormsModule]
})
export class LoginComponent implements OnInit {
  form: LoginFormData = {
    username: null,
    password: null
  };
  user: UserService = {} as UserService;
  apiUrl: string = environment.apiUrl;

  constructor(private route: ActivatedRoute, private userService: UserService) { }

  ngOnInit(): void {
    this.user = this.userService;
    const queryError: string|null = this.route.snapshot.queryParamMap.get('error');
    if ( queryError !== null ) {
      this.user.isLoginFailed = true;
      if ( queryError === 'steam_not_linked' ) {
        this.user.errorMessage = 'This Steam account is not linked to a user.';
      }
      if ( queryError === 'discord_not_linked' ) {
        this.user.errorMessage = 'This Discord account is not linked to a user.';
      }
    }
  }

  onSubmit(): void {
    const { username, password } = this.form;
    const login = document.getElementById('login-button') as HTMLButtonElement;
    if ( login !== null ) {
      login.disabled = true;
    }
    if( username !== null && password !== null ) {
      this.userService.login(username, password);
    }
  }
}

interface LoginFormData {
    username: string|null,
    password: string|null
}
