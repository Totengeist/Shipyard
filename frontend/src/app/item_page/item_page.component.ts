import { Component, OnInit } from '@angular/core';
import { Injectable } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { TokenStorageService } from '../_services/token-storage.service';
import { environment } from '../../environments/environment';

@Component({
    selector: 'app-item-page',
    templateUrl: './item_page.component.html',
    styleUrls: ['./item_page.component.css']
})
export class ItemPageComponent implements OnInit {
    itemType = '';
    itemId = '';
    item: any;
    user: any;
    tags: any[] = [];
    screenshots: any[] = [];
    activeShot = '';

    constructor(private token: TokenStorageService, private http: HttpClient, private route: ActivatedRoute, private router: Router) { }

    ngOnInit(): void {
        this.itemType = this.route.snapshot.data.item_type;
        this.route.params.subscribe(params => {
            this.itemId = params.slug;
            this.getItem(this.itemType, this.itemId).subscribe(
                data => {
                    this.item = data;
                    this.user = data.user;
                    this.tags = data.tags;
                    if (data.primary_screenshot.length > 0) {
                        this.activeShot = data.primary_screenshot[0].ref;
                    }
                },
                err => {
                    console.log('Error');
                }
            );
            this.getScreenshots(this.itemType, this.itemId).subscribe(
                data => {
                    this.screenshots = data;
                    if (this.activeShot === '' && this.screenshots.length > 0) {
                        this.activeShot = this.screenshots[0].ref;
                    }
                },
                err => {
                    console.log('Error');
                }
            );
        });
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
