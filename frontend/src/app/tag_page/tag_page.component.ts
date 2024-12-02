import { Component, OnInit } from '@angular/core';
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { TokenStorageService } from '../_services/token-storage.service';
import { UserService } from '../_services/user.service';
import { environment } from '../../environments/environment';
import { NgFor, NgIf } from '@angular/common';

@Component({
  selector: 'app-tag-page',
  templateUrl: './tag_page.component.html',
  styleUrls: ['./tag_page.component.css'],
  standalone: true,
  imports: [NgFor, NgIf, RouterLink]
})
export class TagPageComponent implements OnInit {
  tag: { slug: string, label: string, description: string } = { slug: '', label: '', description: '' };
  itemTypes: string[] = ['ship', 'save', 'modification'];
  items: Record<string, any[]> = {};

  constructor(private userService: UserService, private token: TokenStorageService, private http: HttpClient, private route: ActivatedRoute, private router: Router) {  }

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      const itemId = params.slug;
      this.getTag(itemId).subscribe(
        data => {
          this.tag = data;
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

  getTag(itemId: string): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', Accept: '*/*' })
    };

    return this.http.get(environment.apiUrl + 'tag/' + itemId, httpOptions);
  }
}
