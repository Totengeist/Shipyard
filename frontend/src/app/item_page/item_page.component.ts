import { NgIf, NgFor, NgClass } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core'; // eslint-disable-line import/named
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { MarkdownComponent } from 'ngx-markdown';
import { Observable } from 'rxjs';
import { ApiService } from '../_services/api.service';
import { TokenStorageService } from '../_services/token-storage.service';
import { UserService } from '../_services/user.service';
import { ItemInterface } from '../_types/item.interface';
import { ScreenshotInterface } from '../_types/screenshot.interface';
import { TagInterface } from '../_types/tag.interface';
import { UserInterface } from '../_types/user.interface';

@Component({
  selector: 'app-item-page',
  templateUrl: './item_page.component.html',
  standalone: true,
  imports: [RouterLink, NgIf, MarkdownComponent, NgFor, NgClass]
})
export class ItemPageComponent implements OnInit {
  private api = inject(ApiService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private token = inject(TokenStorageService);
  private userService = inject(UserService);

  currentUser: UserInterface = {ref: '', name: '', email: ''};
  itemType = '';
  itemId = '';
  item!: ItemInterface;
  parent!: ItemInterface;
  children: ItemInterface[] = [];
  user!: UserInterface;
  tags: TagInterface[] = [];
  screenshots: ScreenshotInterface[] = [];
  activeShot!: ScreenshotInterface;
  authUser: UserService = {} as UserService;

  constructor() {
    this.initializeFields();
  }

  ngOnInit(): void {
    const user = this.token.getUser();
    if( user !== null ) {
      this.currentUser = user;
    }
    this.authUser = this.userService;
    this.initializeFields();
    this.route.params.subscribe(params => {
      this.itemId = params.slug;
      this.getItem(this.itemType, this.itemId).subscribe(
        data => {
          this.item = data;
          this.parent = {ref: '', title: null, description: null, downloads: -1, user: {ref: '', name: '', email: ''}, flags: 0, primary_screenshot: []}
          if( data.parent != null ) {
            this.parent = data.parent;
          }
          if( data.children != null) {
            this.children = data.children;
          }
          if( data.user != null ) {
            this.user = data.user;
          }
          if( data.tags != null) {
            this.tags = data.tags;
          }
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
          console.log('Unable to retrieve screenshot data.');
        }
      );
    });
  }

  initializeFields(): void {
    this.itemType = this.route.snapshot.data.item_type;
    this.item = {ref: '', title: '', description: '', downloads: -1, user: {ref: '', name: '', email: ''}, flags: 0, primary_screenshot: []}
    this.parent = {ref: '', title: null, description: null, downloads: -1, user: {ref: '', name: '', email: ''}, flags: 0, primary_screenshot: []}
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

  getItem(itemType: string, itemId: string): Observable<ItemInterface> {
    return this.api.get<ItemInterface>(`/${itemType}/${itemId}`);
  }

  getScreenshots(itemType: string, itemId: string): Observable<ScreenshotInterface[]> {
    return this.api.get<ScreenshotInterface[]>(`/${itemType}/${itemId}/screenshots`);
  }

  hasScreenshots(): boolean {
    if ( this.screenshots.length > 0 ) {
      return true;
    }
    return false;
  }
}
