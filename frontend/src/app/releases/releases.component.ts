import { NgFor } from '@angular/common';
import { Component, OnInit } from '@angular/core'; // eslint-disable-line import/named
import { RouterLink } from '@angular/router';
import { Observable } from 'rxjs';
import { ApiService } from '../_services/api.service';
import { TokenStorageService } from '../_services/token-storage.service';

@Component({
  selector: 'app-releases',
  templateUrl: './releases.component.html',
  standalone: true,
  imports: [NgFor, RouterLink]
})
export class ReleasesComponent implements OnInit {
  releases: Release[] = [];

  constructor(private api: ApiService, private token: TokenStorageService) { }

  ngOnInit(): void {
    this.getReleases().subscribe(
      data => {
        data.data.forEach((element: Release) => {
          this.releases.push({label: element.label, slug: element.slug});
        });
      },
      () => {
        console.log('Error');
      }
    );
  }

  getReleases(): Observable<any> {
    return this.api.get('/release');
  }
}

interface Release { label: string, slug: string }
