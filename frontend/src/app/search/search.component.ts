import { Component, OnInit, ViewChild, ElementRef, Output, EventEmitter } from '@angular/core';
import { NgIf, NgFor, NgClass } from '@angular/common';
import { HttpClient, HttpHeaders } from '@angular/common/http';

import { from, fromEvent, Observable } from 'rxjs';
import { debounceTime, distinctUntilChanged, map} from 'rxjs/operators';

import { environment } from '../../environments/environment';

@Component({
  selector: 'app-search',
  templateUrl: './search.component.html',
  styleUrls: ['./search.component.css'],
  standalone: true,
  imports: [NgIf, NgFor, NgClass]
})
export class SearchComponent implements OnInit {
  @ViewChild('searchInput', { static: true }) searchInput!: ElementRef;
  @Output() setNameEvent = new EventEmitter<{item: any}>();

  items: any = [];
  showSearches = false;
  isSearching = false;

  constructor(private http: HttpClient) {}

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

  setItemName(item: any): void {
    this.setNameEvent.emit({item});
    this.searchInput.nativeElement.value = '';
    this.items = []
    this.showSearches = false;
  }

  trackById(index: number, item: any): void {
    return item._id;
  }

  closeDropDown(): void {
    this.showSearches = false;
  }

  getItems(searchString: string): Observable<any> {
    if( searchString === '' ) {
      return from(new Promise(resolve => resolve({'data': []})));
    }
    const httpOptions = {
      headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', Accept: '*/*' })
    };

    return this.http.get(environment.apiUrl + 'search/tag/' + searchString, httpOptions);
  }

}
