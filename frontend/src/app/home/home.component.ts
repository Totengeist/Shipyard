import { NgFor, NgIf } from '@angular/common';
import { Component, OnInit } from '@angular/core'; // eslint-disable-line import/named
import { RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { ApiService } from '../_services/api.service';

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  standalone: true,
  imports: [NgFor, NgIf, RouterLink]
})
export class HomeComponent implements OnInit {
  content?: string;
  itemTypes: string[] = ['ship', 'save', 'modification'];
  items: Record<string, any[]> = {};

  constructor(private api: ApiService) { }

  ngOnInit(): void {
    this.getItems();
  }

  getItems(): void {
    this.itemTypes.forEach((itemType: string) => {
      this.items[itemType] = [];
      this.getItemsByType(itemType).subscribe(
        data => {
          data.data.forEach((element: any) => {
            let screen = 'missing.png';
            const screenList = element.primary_screenshot ?? [];
            if (screenList.length > 0) {
              screen = 'api/v1/screenshot/' + screenList[0].ref + '/download';
            }
            this.items[itemType].push({
              title: element.title,
              ref: element.ref,
              description: (element.description ?? ''),
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

  getItemsByType(itemType: string): Observable<any> {
    return this.api.get(`/${itemType}`);
  }
}
