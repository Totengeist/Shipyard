import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { RegisterComponent } from './register/register.component';
import { LoginComponent } from './login/login.component';
import { HomeComponent } from './home/home.component';
import { AboutComponent } from './about/about.component';
import { ProfileComponent } from './profile/profile.component';
import { EditProfileComponent } from './edit_profile/edit_profile.component';
import { ReleasesComponent } from './releases/releases.component';
import { ItemListComponent } from './item_list/item_list.component';
import { ItemPageComponent } from './item_page/item_page.component';
import { ItemEditComponent } from './item_edit/item_edit.component';
import { ItemUploadComponent } from './item_upload/item_upload.component';
import { TagListComponent } from './tag_list/tag_list.component';
import { TagPageComponent } from './tag_page/tag_page.component';
import { UserPageComponent } from './user_page/user_page.component';
import { AdminDashboardComponent } from './admin/dashboard/admin.component';
import { AdminRolesComponent } from './admin/roles/roles.component';
import { AdminPermissionsComponent } from './admin/permissions/permissions.component';
import { AdminEditPermissionsComponent } from './admin/edit-permissions/edit-permissions.component';

const routes: Routes = [
  { path: 'home', component: HomeComponent },
  { path: 'login', component: LoginComponent },
  { path: 'register', component: RegisterComponent },
  { path: 'profile', component: ProfileComponent },
  { path: 'profile/edit', component: EditProfileComponent },
  { path: 'ships/:page', component: ItemListComponent, data: {item_type: 'ship'} },
  { path: 'ships', component: ItemListComponent, data: {item_type: 'ship'} },
  { path: 'saves/:page', component: ItemListComponent, data: {item_type: 'save'} },
  { path: 'saves', component: ItemListComponent, data: {item_type: 'save'} },
  { path: 'mods/:page', component: ItemListComponent, data: {item_type: 'modification'} },
  { path: 'mods', component: ItemListComponent, data: {item_type: 'modification'} },
  { path: 'tags/:page', component: TagListComponent },
  { path: 'tags', component: TagListComponent },
  { path: 'releases', component: ReleasesComponent },
  { path: 'admin/dashboard', component: AdminDashboardComponent },
  { path: 'admin/roles', component: AdminRolesComponent },
  { path: 'admin/role/:slug/edit', component: AdminRolesComponent },
  { path: 'admin/permissions', component: AdminPermissionsComponent },
  { path: 'admin/permission/:slug/edit', component: AdminEditPermissionsComponent },

  { path: 'ship/:slug', component: ItemPageComponent, data: {item_type: 'ship'} },
  { path: 'save/:slug', component: ItemPageComponent, data: {item_type: 'save'} },
  { path: 'modification/:slug', component: ItemPageComponent, data: {item_type: 'modification'} },

  { path: 'ship/:slug/edit', component: ItemEditComponent, data: {item_type: 'ship'} },
  { path: 'save/:slug/edit', component: ItemEditComponent, data: {item_type: 'save'} },
  { path: 'modification/:slug/edit', component: ItemEditComponent, data: {item_type: 'modification'} },

  { path: 'tag/:slug', component: TagPageComponent },
  { path: 'user/:slug', component: UserPageComponent },

  { path: 'new', component: ItemUploadComponent },
  { path: 'about', component: AboutComponent },

  { path: '', redirectTo: '/home', pathMatch: 'full' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
