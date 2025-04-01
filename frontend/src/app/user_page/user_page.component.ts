import { NgFor, NgIf } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core'; // eslint-disable-line import/named
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { ApiService } from '../_services/api.service';
import { TokenStorageService } from '../_services/token-storage.service';
import { UserService } from '../_services/user.service';
import { ItemInterface } from '../_types/item.interface';
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
  private userService = inject(UserService);
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
          this.itemTypes.forEach((element: string) => {
            this.items[element] = data[element+'s' as keyof UserInterface] as ItemInterface[];
          });
          console.log(this.items);
        },
        () => {
          console.log('Error');
        }
      );
    });
  }

  getUser(itemId: string): Observable<UserInterface> {
    return this.api.get(`/user/${itemId}`);
  }
}
