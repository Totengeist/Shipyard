import { NgFor, NgIf } from '@angular/common';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Component, OnInit } from '@angular/core'; // eslint-disable-line import/named
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { TokenStorageService } from '../_services/token-storage.service';
import { UserService } from '../_services/user.service';

@Component({
  selector: 'app-user-page',
  templateUrl: './user_page.component.html',
  styleUrls: ['./user_page.component.css'],
  standalone: true,
  imports: [NgFor, NgIf, RouterLink]
})
export class UserPageComponent implements OnInit {
  name = '';
  itemTypes: string[] = ['ship', 'save', 'modification'];
  items: Record<string, any[]> = {};

  constructor(private userService: UserService, private token: TokenStorageService, private http: HttpClient, private route: ActivatedRoute, private router: Router) {  }

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
    const httpOptions = {
      headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', Accept: '*/*' })
    };

    return this.http.get(environment.apiUrl + 'user/' + itemId, httpOptions);
  }
}
