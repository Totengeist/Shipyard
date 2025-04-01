import { NgFor } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core'; // eslint-disable-line import/named
import { RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { ApiService } from '../../_services/api.service';
import { TokenStorageService } from '../../_services/token-storage.service';
import { PaginationInterface } from '../../_types/pagination.interface';
import { PermissionInterface } from '../../_types/permission.interface';

@Component({
  selector: 'app-permissions',
  templateUrl: './permissions.component.html',
  standalone: true,
  imports: [NgFor, RouterLink]
})
export class AdminPermissionsComponent implements OnInit {
  private api = inject(ApiService);
  private token = inject(TokenStorageService);

  permissions: PermissionInterface[] = [];

  ngOnInit(): void {
    this.getPermissions().subscribe(
      data => {
        if ( data.data !== null ) {
          data.data.forEach((element: PermissionInterface) => {
            this.permissions.push({label: element.label, slug: element.slug});
          });
        }
      },
      () => {
        console.log('Error');
      }
    );
  }

  getPermissions(): Observable<PaginationInterface<PermissionInterface>> {
    return this.api.get('/permission');
  }


}
