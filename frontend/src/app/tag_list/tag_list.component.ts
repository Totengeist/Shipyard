import { Component, OnInit } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { TokenStorageService } from '../_services/token-storage.service';
import { environment } from '../../environments/environment';

@Component({
  selector: 'app-item-list',
  templateUrl: './tag_list.component.html',
  styleUrls: ['./tag_list.component.css']
})
export class TagListComponent implements OnInit {
  tags: any[] = [];
  page = 1;
  lastPage = -1;
  showPrev = false;
  showNext = false;

  constructor(private token: TokenStorageService, private http: HttpClient, private route: ActivatedRoute, private router: Router) { }

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.page = +(params.page ?? 1);
      this.tags = [];
      this.getTags(this.page).subscribe(
        data => {
          this.lastPage = +data.last_page;
          if (this.page > 1) {
            this.showPrev = true;
          } else {
            this.showPrev = false;
          }
          if (this.page < this.lastPage) {
            this.showNext = true;
          } else {
            this.showNext = false;
          }
          data.data.forEach((element: any) => {
            this.tags.push({
              label: element.label,
              slug: element.slug,
              description: (element.description ?? ''),
            });
          });
        },
        () => {
          console.log('Error');
        }
      );
    });
  }

  getTags(page = 1): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', Accept: '*/*' })
    };

    let pageUrl = '';
    if (page > 1) {
      pageUrl = '?page=' + page;
    }

    return this.http.get(environment.apiUrl + 'tag' + pageUrl, httpOptions);
  }

}