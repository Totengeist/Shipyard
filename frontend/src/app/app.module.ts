import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { provideHttpClient, withInterceptorsFromDi } from '@angular/common/http';

import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { LoginComponent } from './login/login.component';
import { RegisterComponent } from './register/register.component';
import { HomeComponent } from './home/home.component';
import { ProfileComponent } from './profile/profile.component';

import { AdminDashboardComponent } from './admin/dashboard/admin.component';
import { AdminRolesComponent } from './admin/roles/roles.component';
import { AdminPermissionsComponent } from './admin/permissions/permissions.component';
import { AdminEditPermissionsComponent } from './admin/edit-permissions/edit-permissions.component';
import { ReleasesComponent } from './releases/releases.component';
import { ItemListComponent } from './item_list/item_list.component';
import { ItemPageComponent } from './item_page/item_page.component';
import { ItemUploadComponent } from './item_upload/item_upload.component';
import { TagListComponent } from './tag_list/tag_list.component';
import { TagPageComponent } from './tag_page/tag_page.component';

@NgModule({ declarations: [
  AppComponent,
  LoginComponent,
  RegisterComponent,
  HomeComponent,
  ProfileComponent,
  AdminDashboardComponent,
  AdminRolesComponent,
  AdminPermissionsComponent,
  AdminEditPermissionsComponent,
  ReleasesComponent,
  ItemListComponent,
  ItemPageComponent,
  ItemUploadComponent,
  TagListComponent,
  TagPageComponent,
],
bootstrap: [AppComponent], imports: [BrowserModule,
  AppRoutingModule,
  FormsModule], providers: [provideHttpClient(withInterceptorsFromDi())] })
export class AppModule { }
