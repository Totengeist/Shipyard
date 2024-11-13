import { Component, OnInit } from '@angular/core';
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { environment } from '../../environments/environment';
import { UserService } from './../_services/user.service';
import Uppy from '@uppy/core';
import Form from '@uppy/form';
import Dashboard from '@uppy/dashboard';
import XHR from '@uppy/xhr-upload';
import { NgIf } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-item-upload',
  templateUrl: './item_upload.component.html',
  styleUrls: ['./item_upload.component.css'],
  standalone: true,
  imports: [FormsModule, NgIf, RouterLink]
})
export class ItemUploadComponent implements OnInit {
  supportedTypes: any = {ship: ["ship file", [".ship"]], save: ["save file", [".space"]], modification: ["mod archive", [".zip"]]}; // eslint-disable-line @typescript-eslint/no-explicit-any
  itemType = "";
  parent = "";
  uppy: any; // eslint-disable-line @typescript-eslint/no-explicit-any
  user: UserService = {} as UserService;

  constructor(private userService: UserService, private route: ActivatedRoute, private router: Router) { }

  ngOnInit(): void {
    this.user = this.userService;
    let availableTypes: string[] = [];
    for (const value of Object.values(this.supportedTypes)) {
      availableTypes = availableTypes.concat((value as any)[1]);
    }
    let selectedTypes = availableTypes;

    if( this.route.snapshot.paramMap.get('parent') !== null ) {
      this.parent = this.route.snapshot.paramMap.get('parent') ?? "";
    }
    if( this.route.snapshot.paramMap.get('itemType') !== null ) {
      const typeCheck = this.route.snapshot.paramMap.get('itemType') ?? "";
      if (typeCheck in this.supportedTypes) {
        this.itemType = typeCheck;
        selectedTypes = (this.supportedTypes as any)[typeCheck][1];
      }
    }

    this.uppy = new Uppy({
      restrictions: {
        allowedFileTypes: selectedTypes,
        maxNumberOfFiles: 1,
      },
    })
      .use(Dashboard, { inline: true, target: '#uppy' })
      .use(Form, {
        target: '#upload',
      })
      .use(XHR, { endpoint: environment.apiUrl+this.itemType })
      .on('file-added', (file) => {
        switch (file.extension) {
        case 'space':
          this.itemType = 'save';
          break;
        case 'ship':
          this.itemType = 'ship';
          break;
        case 'zip':
          this.itemType = 'modification';
          break;
        }
        let endpoint = environment.apiUrl+this.itemType;
        if( this.parent != "" ) {
          endpoint += "/"+this.parent+"/upgrade";
        }
        this.uppy.getPlugin('XHRUpload').setOptions({ endpoint });
      })
      .on('file-removed', () => {
        this.itemType = '';
      })
      .on('upload-success', (file, response) => {
        this.router.navigate(['/'+this.itemType+'/'+response.body!.ref]);
      });
  }

}
