import { NgFor, NgIf } from '@angular/common';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Component, OnInit } from '@angular/core'; // eslint-disable-line import/named
import { ActivatedRoute, RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { TokenStorageService } from '../_services/token-storage.service';
import { UserService } from '../_services/user.service';

@Component({
  selector: 'app-profile',
  templateUrl: './profile.component.html',
  styleUrls: ['./profile.component.css'],
  standalone: true,
  imports: [NgFor, NgIf, RouterLink]
})
export class ProfileComponent implements OnInit {
  currentUser: UserService = {} as UserService;
  steamError = '';
  discordError = '';
  itemTypes: string[] = ['ship', 'save', 'modification'];
  items: Record<string, any[]> = {};
  url: string = environment.standardUrl;

  constructor(private userService: UserService, private route: ActivatedRoute, private token: TokenStorageService, private http: HttpClient) { }

  ngOnInit(): void {
    this.currentUser = this.userService;
    this.userService.refresh();
    //this.currentUser = this.token.getUser();
    const queryError: string|null = this.route.snapshot.queryParamMap.get('error');
    if ( queryError !== null ) {
      if ( queryError === 'steam_already_linked' ) {
        this.steamError = 'This Steam ID is already associated with an existing user.';
      }
      if ( queryError === 'discord_already_linked' ) {
        this.discordError = 'This Discord ID is already associated with an existing user.';
      }
    }
    this.getUser(this.currentUser.ref).subscribe(
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

  removeSteam(): false {
    this.userService.removeSteam().subscribe(
      () => {
        this.userService.refresh();
      },
      () => {
        this.userService.refresh();
      }
    );
    return false;
  }

  removeDiscord(): false {
    this.userService.removeDiscord().subscribe(
      () => {
        this.userService.refresh();
      },
      () => {
        this.userService.refresh();
      }
    );
    return false;
  }

  getUser(itemId: string): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', Accept: '*/*' })
    };

    return this.http.get(environment.apiUrl + 'user/' + itemId, httpOptions);
  }
}
