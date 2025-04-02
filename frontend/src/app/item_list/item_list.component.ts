import { NgFor, NgIf } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core'; // eslint-disable-line import/named
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { ApiService } from '../_services/api.service';
import { TokenStorageService } from '../_services/token-storage.service';
import { ItemInterface } from '../_types/item.interface';
import { PaginationInterface } from '../_types/pagination.interface';
import { ScreenshotInterface } from '../_types/screenshot.interface';

@Component({
  selector: 'app-item-list',
  templateUrl: './item_list.component.html',
  standalone: true,
  imports: [NgFor, RouterLink, NgIf]
})
export class ItemListComponent implements OnInit {
  private api = inject(ApiService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private token = inject(TokenStorageService);

  itemType = '';
  items: ItemInterface[] = [];
  page = 1;
  lastPage = -1;
  showPrev = false;
  showNext = false;

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
          data.data.forEach((element: ItemInterface) => {
            element.description = element.description?.substring(0, 150) ?? '';
            this.items.push(element);
          });
        },
        () => {
          console.log('Error');
        }
      );
    });
  }

  getItems(page = 1): Observable<PaginationInterface<ItemInterface>> {
    let pageUrl = '';
    if (page > 1) {
      pageUrl = '?page=' + page;
    }

    return this.api.get(`/${this.itemType}${pageUrl}`);
  }

  getScreenshotUrl(screenshot: ScreenshotInterface[]|null): string {
    if (screenshot && screenshot.length) {
      return 'api/v1/screenshot/'+screenshot[0].ref+'/preview/318';
    }
    return 'missing.png';
  }
}
