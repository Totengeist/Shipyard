import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
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

  constructor(private router: Router, private userService: UserService) { }

  ngOnInit(): void {
    this.user = this.userService;
  }

  onSubmit(): void {
    const { username, password } = this.form;
    let login = document.getElementById("login-button") as HTMLButtonElement;
    if( login !== null ) {
        login.disabled = true;
    }
    this.userService.login(username, password);
  }
}