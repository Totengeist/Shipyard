import { NgIf, NgFor, NgClass } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core'; // eslint-disable-line import/named
import { FormsModule } from '@angular/forms';
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import Uppy from '@uppy/core';
import Dashboard from '@uppy/dashboard';
import Form from '@uppy/form';
import ImageEditor from '@uppy/image-editor';
import XHR from '@uppy/xhr-upload';
import { MarkdownComponent } from 'ngx-markdown';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { ApiService } from '../_services/api.service';
import { TokenStorageService } from '../_services/token-storage.service';
import { UserService } from '../_services/user.service';
import { ItemInterface } from '../_types/item.interface';
import { ScreenshotInterface } from '../_types/screenshot.interface';
import { TagInterface } from '../_types/tag.interface';
import { UserInterface } from '../_types/user.interface';
import { SearchComponent } from '../search/search.component';

@Component({
  selector: 'app-item-edit',
  templateUrl: './item_edit.component.html',
  standalone: true,
  imports: [SearchComponent, FormsModule, RouterLink, NgIf, MarkdownComponent, NgFor, NgClass]
})
export class ItemEditComponent implements OnInit {
  private api = inject(ApiService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private token = inject(TokenStorageService);
  private userService = inject(UserService);

  supportedTypes: {ship: [string, string[]], save: [string, string[]], modification: [string, string[]]} = {ship: ['ship file', ['.ship']], save: ['save file', ['.space']], modification: ['mod archive', ['.zip']]};
  currentUser: UserInterface|null = null;
  itemType = '';
  itemId = '';
  item!: ItemInterface;
  parent!: ItemInterface|null;
  children: ItemInterface[] = [];
  user!: UserInterface;
  tags: TagInterface[] = [];
  removeTags: string[] = [];
  addTags: string[] = [];
  screenshots: ScreenshotInterface[] = [];
  activeShot!: ScreenshotInterface;
  uppy: Uppy = new Uppy();
  screenshotUppy: Uppy = new Uppy();
  authUser: UserService = {} as UserService;

  constructor() {
    this.initializeFields();
  }

  ngOnInit(): void {
    this.currentUser = this.token.getUser();
    this.authUser = this.userService;
    this.initializeFields();
    this.route.params.subscribe(params => {
      this.itemId = params.slug;
      this.getItem().subscribe(
        data => {
          this.item = data;
          this.parent = null;
          if( data.parent != null ) {
            this.parent = data.parent;
          }
          if( data.children != null ) {
            this.children = data.children;
          }
          if( data.user !== null ) {
            this.user = data.user;
          }
          if (!this.canEdit()) {
            console.log('Unauthorized');
            this.router.navigate(['/'+this.itemType+'/'+this.itemId]);
          }
          if( data.tags ) {
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
      this.updateScreenshots();
    });

    let selectedTypes: string[] = [];
    if (this.itemType in this.supportedTypes) {
      selectedTypes = this.supportedTypes[this.itemType as keyof {ship: [string, string[]], save: [string, string[]], modification: [string, string[]]} ][1];
    }

    this.uppy = new Uppy({
      restrictions: {
        allowedFileTypes: selectedTypes,
        maxNumberOfFiles: 1,
        minNumberOfFiles: 0,
      },
    })
      .use(Dashboard, { inline: true, hideUploadButton: true, target: '#uppy' })
      .use(Form, {
        target: '#edit-form',
        triggerUploadOnSubmit: true,
      })
      .use(XHR, { endpoint: environment.apiUrl+this.itemType })
      .on('file-added', () => {
        const endpoint = environment.apiUrl+this.itemType+'/'+this.itemId;
        this.uppy.getPlugin('XHRUpload')!.setOptions({ endpoint });
      })
      .on('upload-success', () => {
        this.router.navigate(['/'+this.itemType+'/'+this.itemId]);
      })
      .on('upload', (data, files) => {
        if( files.length == 0 ) {
          const uppyDisplay = document.getElementById('uppy')
          if (uppyDisplay !== null) {
            uppyDisplay.style.display = 'none';
          }
          this.uppy.addFile({
            name: '__shipyard__blank__'+selectedTypes[0],
            type: 'text/plain',
            data: new Blob([]),
          });
          this.uppy.upload();
        }
      });

    this.screenshotUppy = new Uppy({
      restrictions: {
        allowedFileTypes: ['.jpg', '.png', '.gif'],
      },
    })
      .use(Dashboard, { inline: true, target: '#screenuppy' })
      .use(Form, {
        target: '#screenshots-form',
      })
      .use(XHR, { endpoint: environment.apiUrl+this.itemType+'/'+this.itemId+'/screenshots' })
      .use(ImageEditor)
      .on('upload-success', (file, response) => {
        if (response.status >= 200 && response.status < 300) {
          const data = JSON.parse(JSON.stringify(response.body));
          this.screenshots = data;
          data.forEach((element: ScreenshotInterface) => {
            if (element.primary) {
              this.activeShot = element;
            }
          });
        }
      });
  }

  initializeFields(): void {
    this.itemType = this.route.snapshot.data.item_type;
    this.item = {ref: '', title: '', description: '', downloads: -1, user: {ref: '', name: '', email: ''}, flags: 0, primary_screenshot: []};
    this.parent = null;
    this.user = {ref: '', name: '', email: ''};
    this.tags = [];
    this.screenshots = [];
    this.activeShot = {ref: '', description: null, primary: true};
  }

  // check if the tag was added
  public removeTag(tag: TagInterface): void {
    this.removeTags.push(tag.slug);
    let index = this.tags.length - 1;
    while (index>= 0) {
      if (this.tags[index].slug == tag.slug) {
        this.tags.splice(index, 1);
      }
      index--;
    }
  }
  // check if the tag was removed
  public addTag(tag: TagInterface): boolean {
    this.addTags.push(tag.slug);
    this.tags.push(tag);
    return false;
  }

  public deleteScreenshot(screenshot: ScreenshotInterface):void {
    const verify = confirm('Are you sure you want to delete this screenshot? This action is irreversible.');
    if (verify) {
      this.api.delete(`/screenshot/${screenshot.ref}`).subscribe(
        () => {
          this.updateScreenshots();
        });
    }
  }

  public editScreenshotDescription(screenshot: ScreenshotInterface):void {
    let description = screenshot.description
    if (description === null) {
      description = '';
    }
    const new_description = prompt('Enter the description for the screenshot:', description);
    if (new_description === description || new_description === null) {
      return;
    }

    this.api.post(`/screenshot/${screenshot.ref}`, {description: new_description}).subscribe(
      () => {
        this.updateScreenshots();
      });
  }

  public makePrimaryScreenshot(screenshot: ScreenshotInterface):void {
    if (screenshot.ref == null) {
      console.log('Unable to mark screenshot as primary. Screenshot ID is unknown.');
      return
    }


    this.api.post(`/${this.itemType}/${this.item.ref}`, {primary_screenshot: screenshot.ref}).subscribe(
      () => {
        this.updateScreenshots();
      });
  }

  public updateScreenshots() {
    this.getScreenshots().subscribe(
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
  }

  hasParent(): boolean {
    if( this.parent === null || this.parent === undefined ) {
      return false;
    }
    return (this.parent.ref !== null);
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
    if (this.currentUser === null) {
      return false;
    }
    return (this.currentUser.ref === this.user.ref)
  }

  parentBelongsToSameUser(): boolean {
    return (this.parent!.user!.ref === this.user.ref)
  }

  getItem(): Observable<ItemInterface> {
    return this.api.get(`/${this.itemType}/${this.itemId}`);
  }

  getScreenshots(): Observable<ScreenshotInterface[]> {
    return this.api.get(`/${this.itemType}/${this.itemId}/screenshots`);
  }

  hasScreenshots(): boolean {
    if ( this.screenshots.length > 0 ) {
      return true;
    }
    return false;
  }
}
