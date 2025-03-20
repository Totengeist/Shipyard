import { NgIf, NgFor, NgClass } from '@angular/common';
import { Component, OnInit, ViewChild, ElementRef, Output, EventEmitter } from '@angular/core'; // eslint-disable-line import/named
import { from, fromEvent, Observable } from 'rxjs';
import { debounceTime, distinctUntilChanged, map} from 'rxjs/operators';
import { ApiService } from '../_services/api.service';
import { PaginationInterface } from '../_types/pagination.interface';
import { TagInterface } from '../_types/tag.interface';

@Component({
  selector: 'app-search',
  templateUrl: './search.component.html',
  styleUrls: ['./search.component.css'],
  standalone: true,
  imports: [NgIf, NgFor, NgClass]
})
export class SearchComponent implements OnInit {
  @ViewChild('searchInput', { static: true }) searchInput!: ElementRef;
  @Output() setNameEvent = new EventEmitter<{item: TagInterface}>();

  items: TagInterface[] = [];
  showSearches = false;
  isSearching = false;

  constructor(private api: ApiService) {}

  ngOnInit() {
    this.itemSearch();
  }

  itemSearch(): void {
    const blur$ = fromEvent(this.searchInput.nativeElement, 'blur');
    const search$ = fromEvent(this.searchInput.nativeElement, 'keyup').pipe(
      map((event: any) => event.target.value.trim()),
      debounceTime(500),
      distinctUntilChanged());

    blur$.subscribe(() => setTimeout(this.closeDropDown, 100));
    search$.subscribe(() => {
      const searchValue = this.searchInput.nativeElement.value.trim();
      if( searchValue !== '' ) {
        this.isSearching = true;
        this.getItems(searchValue).subscribe(
          data => {
            this.items = data.data;
            this.isSearching = false;
            this.showSearches = true;
          },
          () => {
            this.items = [];
            this.isSearching = false;
            this.showSearches = true;
          });
      }
      else {
        this.items = []
        this.isSearching = false;
        this.showSearches = false;
      }
    });
  }

  setItemName(item: TagInterface): void {
    this.setNameEvent.emit({item});
    this.searchInput.nativeElement.value = '';
    this.items = []
    this.showSearches = false;
  }

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  trackById(index: number, item: TagInterface): void {
    return;
  }

  closeDropDown(): void {
    this.showSearches = false;
  }

  getItems(searchString: string): Observable<PaginationInterface<TagInterface>> {
    const return_empty: PaginationInterface<TagInterface> = {
      current_page: 0,
      data: [],
      first_page_url: '',
      from: 0,
      last_page: 0,
      last_page_url: '',
      next_page_url: null,
      path: '',
      per_page: 0,
      prev_page_url: null,
      to: 0,
      total: 0
    }
    if( searchString === '' ) {
      return from(new Promise<PaginationInterface<TagInterface>>(resolve => resolve(return_empty)));
    }

    return this.api.get<PaginationInterface<TagInterface>>(`/search/tag/${searchString}`);
  }

}
