import { NgFor, NgIf } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core'; // eslint-disable-line import/named
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { ApiService } from '../_services/api.service';
import { TokenStorageService } from '../_services/token-storage.service';
import { UserService } from '../_services/user.service';
import { ItemInterface } from '../_types/item.interface';
import { PaginationInterface } from '../_types/pagination.interface';
import { TagInterface } from '../_types/tag.interface';

@Component({
  selector: 'app-tag-page',
  templateUrl: './tag_page.component.html',
  standalone: true,
  imports: [NgFor, NgIf, RouterLink]
})
export class TagPageComponent implements OnInit {
  private api = inject(ApiService);
  private userService = inject(UserService);
  private token = inject(TokenStorageService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);

  tag: TagInterface = { slug: '', label: '', description: '' };
  itemTypes: string[] = ['ship', 'save', 'modification'];
  items: Record<string, ItemInterface[]> = {};

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      const itemId = params.slug;
      this.getTag(itemId).subscribe(
        data => {
          this.tag = data;
          this.getTagItems().subscribe(
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
        },
        () => {
          console.log('Error');
        }
      );
    });
  }

  getTag(itemId: string): Observable<TagInterface> {
    return this.api.get(`/tag/${itemId}`);
  }

  getTagItems(): Observable<PaginationInterface<ItemInterface>> {
    return this.api.get<PaginationInterface<ItemInterface>>(`/search/items?tags=${this.tag.slug}`);
  }
}
