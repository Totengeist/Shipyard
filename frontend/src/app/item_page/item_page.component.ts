import { Component, OnInit } from '@angular/core';
import { Injectable } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { TokenStorageService } from '../_services/token-storage.service';
import { environment } from '../../environments/environment';

@Component({
    selector: 'app-item_page',
    templateUrl: './item_page.component.html',
    styleUrls: ['./item_page.component.css']
})
export class ItemPageComponent implements OnInit {
    item_type: string = "";
    item_id: string = "";
    item: any;
    user: any;
    screenshots: any[] = []
    active_shot: string = ""

    constructor(private token: TokenStorageService, private http: HttpClient, private route: ActivatedRoute, private router: Router) { }

    ngOnInit(): void {
        this.item_type = this.route.snapshot.data['item_type'];
        this.route.params.subscribe(params => {
            this.item_id = params['slug'];
            this.getItem(this.item_type, this.item_id).subscribe(
                data => {
                    this.item = data;
                    this.user = data.user;
                    if (data.primary_screenshot.length > 0) {
                        this.active_shot = data.primary_screenshot[0].ref;
                    }
                },
                err => {
                    console.log("Error");
                }
            );
            this.getScreenshots(this.item_type, this.item_id).subscribe(
                data => {
                    this.screenshots = data;
					if (this.active_shot === "" && this.screenshots.length > 0) {
						this.active_shot = this.screenshots[0].ref;
					}
                },
                err => {
                    console.log("Error");
                }
            );
        });
    }

    getItem(item_type: string, item_id: string): Observable<any> {
        const httpOptions = {
            headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': '*/*' })
        };

        return this.http.get(environment.apiUrl + item_type + '/' + item_id, httpOptions);
    }

    getScreenshots(item_type: string, item_id: string): Observable<any> {
        const httpOptions = {
            headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': '*/*' })
        };

        return this.http.get(environment.apiUrl + item_type + '/' + item_id + '/screenshots', httpOptions);
    }
	
	hasScreenshots() {
		if( this.screenshots.length > 0 ) {
			return true;
		}
		return false;
	}

}
