import { Component, OnInit } from '@angular/core';
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { TokenStorageService } from '../_services/token-storage.service';
import { UserService } from '../_services/user.service';
import { environment } from '../../environments/environment';
import { MarkdownComponent } from 'ngx-markdown';
import Uppy from '@uppy/core';
import Form from '@uppy/form';
import Dashboard from '@uppy/dashboard';
import XHR from '@uppy/xhr-upload';
import { NgIf, NgFor, NgClass } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-item-edit',
  templateUrl: './item_edit.component.html',
  styleUrls: ['./item_edit.component.css'],
  standalone: true,
  imports: [FormsModule, RouterLink, NgIf, MarkdownComponent, NgFor, NgClass]
})
export class ItemEditComponent implements OnInit {
  supportedTypes: any = {ship: ["ship file", [".ship"]], save: ["save file", [".space"]], modification: ["mod archive", [".zip"]]}; // eslint-disable-line @typescript-eslint/no-explicit-any
  currentUser: User = {ref: null, name: null, email: null};
  itemType = '';
  itemId = '';
  item: Item = {ref: null, title: null, description: null, downloads: -1, user: {ref: null, name: null, email: null}};
  parent: Item = {ref: null, title: null, description: null, downloads: -1, user: {ref: null, name: null, email: null}};
  children: Item[] = [];
  user: User = {ref: null, name: null, email: null};
  tags: any[] = [];
  screenshots: Screenshot[] = [];
  activeShot: Screenshot = {ref: null, description: null};
  uppy: any; // eslint-disable-line @typescript-eslint/no-explicit-any
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
          this.parent = {ref: null, title: null, description: null, downloads: -1, user: {ref: null, name: null, email: null}}
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
        const endpoint = environment.apiUrl+this.itemType+"/"+this.itemId;
        this.uppy.getPlugin('XHRUpload').setOptions({ endpoint });
      })
      .on('upload-success', (file, response) => {
        this.router.navigate(['/'+this.itemType+"/"+this.itemId]);
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
  }
  
  initializeFields(): void {
    this.itemType = this.route.snapshot.data.item_type;
    this.item = {ref: null, title: null, description: null, downloads: -1, user: {ref: null, name: null, email: null}}
    this.parent = {ref: null, title: null, description: null, downloads: -1, user: {ref: null, name: null, email: null}}
    this.user = {ref: null, name: null, email: null}
    this.tags = [];
    this.screenshots = []
    this.activeShot = {ref: null, description: null};
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
    user: User
}

interface Screenshot {
    ref: string|null,
    description: string|null
}

interface User {
    ref: string|null,
    name: string|null,
    email: string|null
}
