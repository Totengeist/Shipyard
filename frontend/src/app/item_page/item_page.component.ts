import { NgIf, NgFor, NgClass } from '@angular/common';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Component, OnInit } from '@angular/core'; // eslint-disable-line import/named
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { MarkdownComponent } from 'ngx-markdown';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { TokenStorageService } from '../_services/token-storage.service';
import { UserService } from '../_services/user.service';
import { ItemInterface } from '../_types/item.interface';
import { ScreenshotInterface } from '../_types/screenshot.interface';
import { UserInterface } from '../_types/user.interface';

@Component({
  selector: 'app-item-page',
  templateUrl: './item_page.component.html',
  styleUrls: ['./item_page.component.css'],
  standalone: true,
  imports: [RouterLink, NgIf, MarkdownComponent, NgFor, NgClass]
})
export class ItemPageComponent implements OnInit {
  currentUser: UserInterface = {ref: '', name: '', email: ''};
  itemType = '';
  itemId = '';
  item!: ItemInterface;
  parent!: ItemInterface;
  children: ItemInterface[] = [];
  user!: UserInterface;
  tags: any[] = [];
  screenshots: ScreenshotInterface[] = [];
  activeShot!: ScreenshotInterface;
  authUser: UserService = {} as UserService;

  constructor(private userService: UserService, private token: TokenStorageService, private http: HttpClient, private route: ActivatedRoute, private router: Router) {
    this.initializeFields();
  }

  ngOnInit(): void {
    if( this.token.getUser() !== null ) {
      this.currentUser = this.token.getUser();
    }
    this.authUser = this.userService;
    this.initializeFields();
    this.route.params.subscribe(params => {
      this.itemId = params.slug;
      this.getItem(this.itemType, this.itemId).subscribe(
        data => {
          this.item = data;
          this.parent = {ref: '', title: null, description: null, downloads: -1, user: {ref: '', name: '', email: ''}, flags: 0}
          if( data.parent !== null ) {
            this.parent = data.parent;
          }
          this.children = data.children;
          if( data.user !== null ) {
            this.user = data.user;
          }
          this.tags = data.tags;
          if (data.primary_screenshot.length > 0) {
            this.activeShot = data.primary_screenshot[0];
          }
        },
        () => {
          console.log('Error');
        }
      );
      this.getScreenshots(this.itemType, this.itemId).subscribe(
        data => {
          this.screenshots = data;
          if (this.activeShot.ref === null && this.screenshots.length > 0) {
            this.activeShot = this.screenshots[0];
          }
        },
        () => {
          console.log('Error');
        }
      );
    });
  }

  initializeFields(): void {
    this.itemType = this.route.snapshot.data.item_type;
    this.item = {ref: '', title: '', description: '', downloads: -1, user: {ref: '', name: '', email: ''}, flags: 0}
    this.parent = {ref: '', title: null, description: null, downloads: -1, user: {ref: '', name: '', email: ''}, flags: 0}
    this.user = {ref: '', name: '', email: ''}
    this.tags = [];
    this.screenshots = []
    this.activeShot = {ref: '', description: null, primary: false};
  }

  setActiveScreenshot(screenshot: ScreenshotInterface): void {
    this.activeShot = screenshot;
  }

  hasParent(): boolean {
    if( this.parent === null || this.parent === undefined ) {
      return false;
    }
    return (this.parent.ref !== '');
  }

  hasChildren(): boolean {
    if (this.children === null || this.children === undefined ) {
      return false;
    }
    return (this.children.length > 0);
  }

  isPrivate(): boolean {
    return (this.item.flags & 1) == 1;
  }

  isUnlisted(): boolean {
    return (this.item.flags & 2) == 2;
  }

  isLocked(): boolean {
    return (this.item.flags & 4) == 4;
  }

  canEdit(): boolean {
    return this.authUser.can('edit '+this.itemType+'s')||(this.belongsToCurrentUser() && !this.isLocked());
  }

  belongsToCurrentUser(): boolean {
    if (this.currentUser.ref === null) {
      return false;
    }
    return (this.currentUser.ref === this.user.ref)
  }

  parentBelongsToSameUser(): boolean {
    return (this.parent!.user!.ref === this.user.ref)
  }

  getItem(itemType: string, itemId: string): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', Accept: '*/*' })
    };

    return this.http.get(environment.apiUrl + itemType + '/' + itemId, httpOptions);
  }

  getScreenshots(itemType: string, itemId: string): Observable<any> {
    const httpOptions = {
      headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', Accept: '*/*' })
    };

    return this.http.get(environment.apiUrl + itemType + '/' + itemId + '/screenshots', httpOptions);
  }

  hasScreenshots(): boolean {
    if ( this.screenshots.length > 0 ) {
      return true;
    }
    return false;
  }
}
