import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { TokenStorageService } from './_services/token-storage.service';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent implements OnInit {
  private roles: string[] = [];
  isLoggedIn = false;
  showDashboard = false;
  username: string = "";

  constructor(private tokenStorageService: TokenStorageService, private router: Router) { }

  ngOnInit(): void {
    this.isLoggedIn = !!this.tokenStorageService.getToken();

    if (this.isLoggedIn) {
      const user = this.tokenStorageService.getUser();
      this.roles = user.roles;
      this.showDashboard = (this.roles.length > 0);
      this.username = user.name;
    }
  }
  
  isDashboard(): boolean {
    return (this.router.url.split("/", -1)[1] == "admin");
  }

  logout(): void {
    this.tokenStorageService.signOut();
    window.location.reload();
  }
}