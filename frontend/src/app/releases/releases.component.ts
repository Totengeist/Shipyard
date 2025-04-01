import { NgFor } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core'; // eslint-disable-line import/named
import { RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { ApiService } from '../_services/api.service';
import { TokenStorageService } from '../_services/token-storage.service';
import { PaginationInterface } from '../_types/pagination.interface';
import { ReleaseInterface } from '../_types/release.interface';

@Component({
  selector: 'app-releases',
  templateUrl: './releases.component.html',
  standalone: true,
  imports: [NgFor, RouterLink]
})
export class ReleasesComponent implements OnInit {
  private api = inject(ApiService);
  private token = inject(TokenStorageService);

  releases: ReleaseInterface[] = [];

  ngOnInit(): void {
    this.getReleases().subscribe(
      data => {
        data.data.forEach((element: ReleaseInterface) => {
          this.releases.push({label: element.label, slug: element.slug});
        });
      },
      () => {
        console.log('Error');
      }
    );
  }

  getReleases(): Observable<PaginationInterface<ReleaseInterface>> {
    return this.api.get<PaginationInterface<ReleaseInterface>>('/release');
  }
}
