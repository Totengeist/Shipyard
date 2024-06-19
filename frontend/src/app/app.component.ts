import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from './_services/auth.service';
import { TokenStorageService } from './_services/token-storage.service';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent implements OnInit {
  private roles: string[] = [];
  isLoggedIn: boolean = false;
  showDashboard: boolean = false;
  username: string = "";

  constructor(private authService: AuthService, private tokenStorageService: TokenStorageService, private router: Router) { }

  ngOnInit(): void {
    this.isLoggedIn = !!this.tokenStorageService.getUser();

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
    this.authService.logout().subscribe(
      data => {
        this.tokenStorageService.signOut();
        this.isLoggedIn = false;
        this.username = "";
        this.showDashboard = false;
        this.roles = [];
        this.router.navigate(['/home'])
      },
      err => {
        alert( err.message );
      }
    );
  }
}