import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { TokenStorageService } from '../_services/token-storage.service';
import { UserService } from '../_services/user.service';
import { NgIf } from '@angular/common';

@Component({
  selector: 'app-profile',
  templateUrl: './profile.component.html',
  styleUrls: ['./profile.component.css'],
  standalone: true,
  imports: [RouterLink, NgIf]
})
export class ProfileComponent implements OnInit {
  currentUser: User = {
    name: null,
    email: null,
    hasSteamLogin: false,
    hasDiscordLogin: false
  };
  steamError = '';
  discordError = '';

  constructor(private userService: UserService, private route: ActivatedRoute, private token: TokenStorageService) { }

  ngOnInit(): void {
    this.currentUser = this.token.getUser();
    const queryError: string|null = this.route.snapshot.queryParamMap.get('error');
    if ( queryError !== null ) {
      if ( queryError === 'steam_already_linked' ) {
        this.steamError = 'This Steam ID is already associated with an existing user.';
      }
      if ( queryError === 'discord_already_linked' ) {
        this.discordError = 'This Discord ID is already associated with an existing user.';
      }
    }
  }

  removeSteam(): void {
    this.userService.removeSteam().subscribe(
      () => {
        location.reload();
      },
      () => {
        location.reload();
      }
    );
  }

  removeDiscord(): void {
    this.userService.removeDiscord().subscribe(
      () => {
        location.reload();
      },
      () => {
        location.reload();
      }
    );
  }
}

interface User {
    name: string|null,
    email: string|null,
    hasSteamLogin: boolean
    hasDiscordLogin: boolean
}