import { Component, OnInit } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { TokenStorageService } from '../_services/token-storage.service';
import { environment } from '../../environments/environment';

@Component({
  selector: 'app-item-list',
  templateUrl: './item_list.component.html',
  styleUrls: ['./item_list.component.css']
})
export class ItemListComponent implements OnInit {
  itemType = '';
  items: any[] = [];
  page = 1;
  lastPage = -1;
  showPrev = false;
  showNext = false;

  constructor(private token: TokenStorageService, private http: HttpClient, private route: ActivatedRoute, private router: Router) { }

  ngOnInit(): void {
    this.itemType = this.route.snapshot.data.item_type;
    this.route.params.subscribe(params => {
      this.page = +(params.page ?? 1);
      this.items = [];
      this.getItems(this.page).subscribe(
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
            let screen = 'missing.png';
            const screenList = element.primary_screenshot ?? [];
            if (screenList.length > 0) {
              screen = 'api/v1/screenshot/' + screenList[0].ref + '/download';
            }
            this.items.push({
              title: element.title,
              ref: element.ref,
              description: (element.description.substring(0, 150) ?? ''),
              username: (element.user?.name ?? '' ),
              userref: (element.user?.ref ?? ''),
              screenshot: screen
            });
          });
        },
        () => {
          console.log('Error');
        }
      );
    });
  }

  getItems(page = 1): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', Accept: '*/*' })
    };

    let pageUrl = '';
    if (page > 1) {
      pageUrl = '?page=' + page;
    }

    return this.http.get(environment.apiUrl + this.itemType + pageUrl, httpOptions);
  }

}
