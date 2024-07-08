import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { TokenStorageService } from '../_services/token-storage.service';
import { UserService } from '../_services/user.service';

@Component({
  selector: 'app-profile',
  templateUrl: './profile.component.html',
  styleUrls: ['./profile.component.css']
})
export class ProfileComponent implements OnInit {
  currentUser: any;
  steamError = '';

  constructor(private userService: UserService, private route: ActivatedRoute, private token: TokenStorageService) { }

  ngOnInit(): void {
    this.currentUser = this.token.getUser();
    const queryError: string|null = this.route.snapshot.queryParamMap.get('error');
    if ( queryError !== null ) {
        if ( queryError === 'steam_already_linked' ) {
            this.steamError = 'This Steam ID is already associated with an existing user.';
        }
    }
  }

  removeSteam(): void {
    this.userService.removeSteam().subscribe(
      data => {
          location.reload();
      },
      err => {
          location.reload();
      }
    );
  }
}
