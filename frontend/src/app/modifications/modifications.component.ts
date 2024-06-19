import { Component, OnInit } from '@angular/core';
import { Injectable } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { TokenStorageService } from '../_services/token-storage.service';
import { environment } from '../../environments/environment';

@Component({
    selector: 'app-modifications',
    templateUrl: './modifications.component.html',
    styleUrls: ['./modifications.component.css']
})
export class ModificationsComponent implements OnInit {
    modifications: any[] = [];
    page: number = 1;
    last_page: number = -1;
    show_next: boolean = false;
    show_prev: boolean = false;

    constructor(private token: TokenStorageService, private http: HttpClient, private route: ActivatedRoute, private router: Router) { }

    ngOnInit(): void {
        this.route.params.subscribe(params => {
            this.page = +(params['page'] ?? 1);
            this.modifications = [];
            this.getModifications(this.page).subscribe(
                data => {
                    this.last_page = +data.last_page;
                    if (this.page > 1) {
                        this.show_prev = true;
                    } else {
                        this.show_prev = false;
                    }
                    if (this.page < this.last_page) {
                        this.show_next = true;
                    } else {
                        this.show_next = false;
                    }
                    data.data.forEach((element: any) => {
                        let screen = "missing.png"
                        let screen_list = element.primary_screenshot ?? [];
                        if (screen_list.length > 0) {
                            screen = "api/v1/screenshot/"+screen_list[0].ref+"/download";
                        }
                        this.modifications.push({
                            title: element.title,
                            ref: element.ref,
                            description: (element.description ?? ""),
                            username: (element.user?.name ?? "" ),
                            userref: (element.user?.ref ?? ""),
                            screenshot: screen
                        });
                    });
                },
                err => {
                    console.log("Error");
                }
            );
        });
    }

    getModifications(page = 1): Observable<any> {
        const httpOptions = {
            headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': '*/*' })
        };

        let pageUrl = '';
        if (page > 1) {
            pageUrl = '?page='+page;
        }

        return this.http.get(environment.apiUrl + 'modification' + pageUrl, httpOptions);
  }

}
