import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { UserService } from './_services/user.service';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent implements OnInit {
  user: UserService = {} as UserService;

  constructor(private userService: UserService, private router: Router) { }

  ngOnInit(): void {
    this.user = this.userService;
    this.userService.refresh();
  }

  isDashboard(): boolean {
    return (this.router.url.split('/', -1)[1] === 'admin');
  }

  showDashboard(): boolean {
    return (this.userService.roles.length > 0);
  }
}
