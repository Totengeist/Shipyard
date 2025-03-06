import { NgFor, NgIf } from '@angular/common';
import { Component, OnInit } from '@angular/core'; // eslint-disable-line import/named
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { ApiService } from '../_services/api.service';
import { TokenStorageService } from '../_services/token-storage.service';
import { UserService } from '../_services/user.service';

@Component({
  selector: 'app-user-page',
  templateUrl: './user_page.component.html',
  standalone: true,
  imports: [NgFor, NgIf, RouterLink]
})
export class UserPageComponent implements OnInit {
  name = '';
  itemTypes: string[] = ['ship', 'save', 'modification'];
  items: Record<string, any[]> = {};

  constructor(private api: ApiService, private token: TokenStorageService, private userService: UserService, private route: ActivatedRoute, private router: Router) {  }

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      const itemId = params.slug;
      this.getUser(itemId).subscribe(
        data => {
          this.name = data.name;
          this.itemTypes.forEach((element: string) => {
            this.items[element] = data[element+'s'];
          });
          console.log(this.items);
        },
        () => {
          console.log('Error');
        }
      );
    });
  }

  getUser(itemId: string): Observable<any> {
    return this.api.get(`/user/${itemId}`);
  }
}
