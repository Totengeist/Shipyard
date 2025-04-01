import { NgFor, NgIf } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core'; // eslint-disable-line import/named
import { ActivatedRoute, RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { ApiService } from '../_services/api.service';
import { TokenStorageService } from '../_services/token-storage.service';
import { UserService } from '../_services/user.service';
import { ItemInterface } from '../_types/item.interface';
import { UserInterface } from '../_types/user.interface';

@Component({
  selector: 'app-profile',
  templateUrl: './profile.component.html',
  standalone: true,
  imports: [NgFor, NgIf, RouterLink]
})
export class ProfileComponent implements OnInit {
  private api = inject(ApiService);
  private route = inject(ActivatedRoute);
  private token = inject(TokenStorageService);
  private userService = inject(UserService);

  currentUser: UserService = {} as UserService;
  steamError = '';
  discordError = '';
  itemTypes: string[] = ['ship', 'save', 'modification'];
  items: Record<string, ItemInterface[]> = {};
  url: string = environment.standardUrl;

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
          this.items[element] = data[element+'s' as keyof UserInterface] as ItemInterface[];
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

  getUser(itemId: string): Observable<UserInterface> {
    return this.api.get<UserInterface>(`/user/${itemId}`);
  }
}
