import { NgFor, NgIf } from '@angular/common';
import { Component, OnInit } from '@angular/core'; // eslint-disable-line import/named
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { ApiService } from '../_services/api.service';
import { TokenStorageService } from '../_services/token-storage.service';
import { UserService } from '../_services/user.service';
import { ItemInterface } from '../_types/item.interface';
import { TagInterface } from '../_types/tag.interface';

@Component({
  selector: 'app-tag-page',
  templateUrl: './tag_page.component.html',
  standalone: true,
  imports: [NgFor, NgIf, RouterLink]
})
export class TagPageComponent implements OnInit {
  tag: TagInterface = { slug: '', label: '', description: '' };
  itemTypes: string[] = ['ship', 'save', 'modification'];
  items: Record<string, ItemInterface[]> = {};

  constructor(private api: ApiService, private userService: UserService, private token: TokenStorageService, private route: ActivatedRoute, private router: Router) {  }

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      const itemId = params.slug;
      this.getTag(itemId).subscribe(
        data => {
          this.tag = data;
          this.itemTypes.forEach((element: string) => {
            this.items[element] = data[element+'s' as keyof TagInterface] as ItemInterface[];
          });
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
}
