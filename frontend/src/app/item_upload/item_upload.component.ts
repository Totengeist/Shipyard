import { NgIf } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core'; // eslint-disable-line import/named
import { FormsModule } from '@angular/forms';
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import Uppy from '@uppy/core';
import Dashboard from '@uppy/dashboard';
import Form from '@uppy/form';
import XHR from '@uppy/xhr-upload';
import { environment } from '../../environments/environment';
import { UserService } from './../_services/user.service';

@Component({
  selector: 'app-item-upload',
  templateUrl: './item_upload.component.html',
  standalone: true,
  imports: [FormsModule, NgIf, RouterLink]
})
export class ItemUploadComponent implements OnInit {
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private userService = inject(UserService);

  supportedTypes: {ship: [string, string[]], save: [string, string[]], modification: [string, string[]]} = {ship: ['ship file', ['.ship']], save: ['save file', ['.space']], modification: ['mod archive', ['.zip']]};
  itemType: keyof {ship: [string, string[]], save: [string, string[]], modification: [string, string[]]}|'' = '';
  parent = '';
  uppy: Uppy = new Uppy();
  user: UserService = {} as UserService;

  ngOnInit(): void {
    this.user = this.userService;
    let availableTypes: string[] = [];
    for (const value of Object.values(this.supportedTypes)) {
      availableTypes = availableTypes.concat((value)[1]);
    }
    let selectedTypes = availableTypes;

    if( this.route.snapshot.paramMap.get('parent') !== null ) {
      this.parent = this.route.snapshot.paramMap.get('parent') ?? '';
    }
    if( this.route.snapshot.paramMap.get('itemType') !== null ) {
      const typeCheck = this.route.snapshot.paramMap.get('itemType') ?? '';
      if (typeCheck in this.supportedTypes) {
        this.itemType = typeCheck as keyof {ship: [string, string[]], save: [string, string[]], modification: [string, string[]]};
        selectedTypes = this.supportedTypes[this.itemType][1];
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
        if( this.parent != '' ) {
          endpoint += '/'+this.parent+'/upgrade';
        }
        this.uppy.getPlugin('XHRUpload')?.setOptions({ endpoint });
      })
      .on('file-removed', () => {
        this.itemType = '';
      })
      .on('upload-success', (file, response) => {
        this.router.navigate(['/'+this.itemType+'/'+response.body!.ref]);
      });
  }

}
