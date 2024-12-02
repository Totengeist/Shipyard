import { Component, OnInit } from '@angular/core';
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { TokenStorageService } from '../_services/token-storage.service';
import { UserService } from '../_services/user.service';
import { environment } from '../../environments/environment';
import { SearchComponent } from '../search/search.component';
import { MarkdownComponent } from 'ngx-markdown';
import Uppy from '@uppy/core';
import Form from '@uppy/form';
import Dashboard from '@uppy/dashboard';
import XHR from '@uppy/xhr-upload';
import ImageEditor from '@uppy/image-editor';
import { NgIf, NgFor, NgClass } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-item-edit',
  templateUrl: './item_edit.component.html',
  styleUrls: ['./item_edit.component.css'],
  standalone: true,
  imports: [SearchComponent, FormsModule, RouterLink, NgIf, MarkdownComponent, NgFor, NgClass]
})
export class ItemEditComponent implements OnInit {
  supportedTypes: any = {ship: ['ship file', ['.ship']], save: ['save file', ['.space']], modification: ['mod archive', ['.zip']]}; // eslint-disable-line @typescript-eslint/no-explicit-any
  currentUser: User = {ref: null, name: null, email: null};
  itemType = '';
  itemId = '';
  item: Item = {ref: null, title: null, description: null, downloads: -1, user: {ref: null, name: null, email: null}, flags: 0};
  parent: Item = {ref: null, title: null, description: null, downloads: -1, user: {ref: null, name: null, email: null}, flags: 0};
  children: Item[] = [];
  user: User = {ref: null, name: null, email: null};
  tags: any[] = [];
  removeTags: string[] = [];
  addTags: string[] = [];
  screenshots: Screenshot[] = [];
  activeShot: Screenshot = {ref: null, description: null, primary: true};
  uppy: Uppy = new Uppy();
  screenshotUppy: Uppy = new Uppy();
  userServ: UserService = {} as UserService;

  constructor(private userService: UserService, private token: TokenStorageService, private http: HttpClient, private route: ActivatedRoute, private router: Router) {
    this.initializeFields();
  }

  ngOnInit(): void {
    if( this.token.getUser() !== null ) {
      this.currentUser = this.token.getUser();
    }
    this.initializeFields();
    this.route.params.subscribe(params => {
      this.itemId = params.slug;
      this.getItem(this.itemType, this.itemId).subscribe(
        data => {
          this.item = data;
          this.parent = {ref: null, title: null, description: null, downloads: -1, user: {ref: null, name: null, email: null}, flags: 0}
          if( data.parent !== null ) {
            this.parent = data.parent;
          }
          this.children = data.children;
          if( data.user !== null ) {
            this.user = data.user;
          }
          if (this.currentUser.ref !== this.user.ref) {
            this.router.navigate(['/']);
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
      this.updateScreenshots();
    });

    let selectedTypes = [];
    if (this.itemType in this.supportedTypes) {
      selectedTypes = (this.supportedTypes as any)[this.itemType][1];
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
      .on('file-added', (file) => {
        const endpoint = environment.apiUrl+this.itemType+'/'+this.itemId;
        this.uppy.getPlugin('XHRUpload')!.setOptions({ endpoint });
      })
      .on('upload-success', (file, response) => {
        this.router.navigate(['/'+this.itemType+'/'+this.itemId]);
      })
      .on('upload', (data, files) => {
        if( files.length == 0 ) {
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
        console.log(response.body);
        if (response.status >= 200 && response.status < 300) {
          const data = JSON.parse(JSON.stringify(response.body));
          this.screenshots = data;
          data.forEach((element: Screenshot) => {
            if (element.primary) {
              this.activeShot = element;
            }
          });
        }
      });
  }

  initializeFields(): void {
    this.itemType = this.route.snapshot.data.item_type;
    this.item = {ref: null, title: null, description: null, downloads: -1, user: {ref: null, name: null, email: null}, flags: 0}
    this.parent = {ref: null, title: null, description: null, downloads: -1, user: {ref: null, name: null, email: null}, flags: 0}
    this.user = {ref: null, name: null, email: null}
    this.tags = [];
    this.screenshots = [];
    this.activeShot = {ref: null, description: null, primary: true};
  }

  // check if the tag was added
  public removeTag(tag:any): void {
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
  public addTag(item:any): boolean {
    console.log(item);
    this.addTags.push(item.slug);
    this.tags.push(item);
    return false;
  }

  public deleteScreenshot(screenshot: Screenshot):void {
    const verify = confirm('Are you sure you want to delete this screenshot? This action is irreversible.');
    if (verify) {
      const httpOptions = {
        headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', Accept: '*/*' })
      };

      this.http.delete(environment.apiUrl + 'screenshot/' + screenshot.ref, httpOptions).subscribe(
        () => {
          this.updateScreenshots();
        });
    }
  }

  public editScreenshotDescription(screenshot: Screenshot):void {
    let description = screenshot.description
    if (description === null) {
      description = '';
    }
    const new_description = prompt('Enter the description for the screenshot:', description);
    if (new_description === description || new_description === null) {
      return;
    }

    const httpOptions = {
      headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', Accept: '*/*' })
    };
    const body = new URLSearchParams();
    body.set('description', new_description);

    this.http.post(environment.apiUrl + 'screenshot/' + screenshot.ref, body.toString(), httpOptions).subscribe(
      () => {
        this.updateScreenshots();
      });
  }

  public makePrimaryScreenshot(screenshot: Screenshot):void {
    console.log('primarying');
  }

  public updateScreenshots() {
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

interface Item {
    ref: string|null,
    title: string|null,
    description: string|null
    downloads: number,
    user: User,
    flags: number
}

interface Screenshot {
    ref: string|null,
    description: string|null,
    primary: boolean
}

interface User {
    ref: string|null,
    name: string|null,
    email: string|null
}
