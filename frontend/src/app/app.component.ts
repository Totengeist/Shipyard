import { NgIf } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core'; // eslint-disable-line import/named
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { ApiService } from './_services/api.service';
import { UserService } from './_services/user.service';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  standalone: true,
  imports: [RouterLink, RouterLinkActive, NgIf, RouterOutlet]
})
export class AppComponent implements OnInit {
  private api = inject(ApiService);
  private router = inject(Router);
  private userService = inject(UserService);

  user: UserService = {} as UserService;
  version: Record<string,string> = {app: 'Shipyard', version: '', commit: ''};

  ngOnInit(): void {
    this.user = this.userService;
    this.userService.refresh();

    this.api.get<Record<string,string>>('/version').subscribe(
      data => {
        this.version = data;
      },
      () => {
        console.log('Error');
      }
    );
  }

  isDashboard(): boolean {
    return (this.router.url.split('/', -1)[1] === 'admin');
  }

  isAbout(): boolean {
    return (this.router.url.split('/', -1)[1] === 'about');
  }

  showDashboard(): boolean {
    return (this.userService.roles.length > 0);
  }
}
