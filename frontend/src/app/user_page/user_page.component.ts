import { NgFor, NgIf } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core'; // eslint-disable-line import/named
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { ApiService } from '../_services/api.service';
import { TokenStorageService } from '../_services/token-storage.service';
import { ItemInterface } from '../_types/item.interface';
import { PaginationInterface } from '../_types/pagination.interface';
import { UserInterface } from '../_types/user.interface';

@Component({
  selector: 'app-user-page',
  templateUrl: './user_page.component.html',
  standalone: true,
  imports: [NgFor, NgIf, RouterLink]
})
export class UserPageComponent implements OnInit {
  private api = inject(ApiService);
  private token = inject(TokenStorageService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);

  name = '';
  itemTypes: string[] = ['ship', 'save', 'modification'];
  items: Record<string, ItemInterface[]> = {};

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      const itemId = params.slug;
      this.getUser(itemId).subscribe(
        data => {
          this.name = data.name ?? '';
        },
        () => {
          console.log('Error');
        }
      );
      this.getUserItems(itemId).subscribe(
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
    });
  }

  getUser(userId: string): Observable<UserInterface> {
    return this.api.get(`/user/${userId}`);
  }

  getUserItems(userId: string): Observable<PaginationInterface<ItemInterface>> {
    return this.api.get<PaginationInterface<ItemInterface>>(`/search/items?user=${userId}`);
  }
}
