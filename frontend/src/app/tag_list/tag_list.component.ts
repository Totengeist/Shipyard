import { NgFor, NgIf } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core'; // eslint-disable-line import/named
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { ApiService } from '../_services/api.service';
import { TokenStorageService } from '../_services/token-storage.service';
import { PaginationInterface } from '../_types/pagination.interface';
import { TagInterface } from '../_types/tag.interface';

@Component({
  selector: 'app-item-list',
  templateUrl: './tag_list.component.html',
  standalone: true,
  imports: [NgFor, RouterLink, NgIf]
})
export class TagListComponent implements OnInit {
  private api = inject(ApiService);
  private token = inject(TokenStorageService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);

  tags: TagInterface[] = [];
  page = 1;
  lastPage = -1;
  showPrev = false;
  showNext = false;

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
          data.data.forEach((element: TagInterface) => {
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

  getTags(page = 1): Observable<PaginationInterface<TagInterface>> {
    let pageUrl = '';
    if (page > 1) {
      pageUrl = '?page=' + page;
    }

    return this.api.get(`/tag${pageUrl}`);
  }

}
