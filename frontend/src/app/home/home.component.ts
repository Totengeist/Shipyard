import { NgFor, NgIf } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core'; // eslint-disable-line import/named
import { RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { ApiService } from '../_services/api.service';
import { ItemInterface } from '../_types/item.interface';
import { PaginationInterface } from '../_types/pagination.interface';

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  standalone: true,
  imports: [NgFor, NgIf, RouterLink]
})
export class HomeComponent implements OnInit {
  private api = inject(ApiService);

  content?: string;
  itemTypes: string[] = ['ship', 'save', 'modification'];
  items: Record<string, ItemInterface[]> = {};

  ngOnInit(): void {
    this.getItems();
  }

  getItems(): void {
    this.itemTypes.forEach((itemType: string) => {
      this.items[itemType] = [];
      this.getItemsByType(itemType).subscribe(
        data => {
          data.data.forEach((element: ItemInterface) => {
            this.items[itemType].push(element);
          });
        },
        () => {
          console.log('Error');
        }
      );
    });
  }

  getItemsByType(itemType: string): Observable<PaginationInterface<ItemInterface>> {
    return this.api.get<PaginationInterface<ItemInterface>>(`/${itemType}`);
  }
}
