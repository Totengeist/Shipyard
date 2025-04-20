import { NgFor, NgIf } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core'; // eslint-disable-line import/named
import { ActivatedRoute, RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { ApiService } from '../_services/api.service';
import { TokenStorageService } from '../_services/token-storage.service';
import { UserService } from '../_services/user.service';
import { ItemInterface } from '../_types/item.interface';
import { PaginationInterface } from '../_types/pagination.interface';

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
    const queryError: string|null = this.route.snapshot.queryParamMap.get('error');
    if ( queryError !== null ) {
      if ( queryError === 'steam_already_linked' ) {
        this.steamError = 'This Steam ID is already associated with an existing user.';
      }
      if ( queryError === 'discord_already_linked' ) {
        this.discordError = 'This Discord ID is already associated with an existing user.';
      }
    }
    this.getUserItems(this.currentUser.ref).subscribe(
      data => {
        data.data.forEach((element: ItemInterface) => {
          if (this.items[element.item_type!] === undefined) {
            this.items[element.item_type!] = [element];
          } else {
            this.items[element.item_type!].push(element);
          }
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

  getUserItems(userId: string): Observable<PaginationInterface<ItemInterface>> {
    return this.api.get<PaginationInterface<ItemInterface>>(`/search/items?user=${userId}`);
  }
}
