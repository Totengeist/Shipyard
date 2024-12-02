import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { TokenStorageService } from '../_services/token-storage.service';
import { UserService } from '../_services/user.service';
import { environment } from '../../environments/environment';
import { NgFor, NgIf } from '@angular/common';

@Component({
  selector: 'app-profile',
  templateUrl: './profile.component.html',
  styleUrls: ['./profile.component.css'],
  standalone: true,
  imports: [NgFor, NgIf, RouterLink]
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
  itemTypes: string[] = ['ship', 'save', 'modification'];
  items: Record<string, any[]> = {};

  constructor(private userService: UserService, private route: ActivatedRoute, private token: TokenStorageService, private http: HttpClient) { }

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
    this.getUser(this.token.getUser().ref).subscribe(
      data => {
        this.itemTypes.forEach((element: string) => {
          this.items[element] = data[element+'s'];
        });
      },
      () => {
        console.log('Error');
      }
    );
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

  getUser(itemId: string): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', Accept: '*/*' })
    };

    return this.http.get(environment.apiUrl + 'user/' + itemId, httpOptions);
  }
}

interface User {
    name: string|null,
    email: string|null,
    hasSteamLogin: boolean
    hasDiscordLogin: boolean
}
