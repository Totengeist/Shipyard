import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { RegisterComponent } from './register/register.component';
import { LoginComponent } from './login/login.component';
import { HomeComponent } from './home/home.component';
import { ProfileComponent } from './profile/profile.component';
import { ShipsComponent } from './ships/ships.component';
import { ChallengesComponent } from './challenges/challenges.component';
import { SavesComponent } from './saves/saves.component';
import { ReleasesComponent } from './releases/releases.component';
import { AdminDashboardComponent } from './admin/dashboard/admin.component';
import { AdminRolesComponent } from './admin/roles/roles.component';
import { AdminPermissionsComponent } from './admin/permissions/permissions.component';
import { AdminEditPermissionsComponent } from './admin/edit-permissions/edit-permissions.component';

const routes: Routes = [
  { path: 'home', component: HomeComponent },
  { path: 'login', component: LoginComponent },
  { path: 'register', component: RegisterComponent },
  { path: 'profile', component: ProfileComponent },
  { path: 'ships', component: ShipsComponent },
  { path: 'challenges', component: ChallengesComponent },
  { path: 'saves', component: SavesComponent },
  { path: 'releases', component:ReleasesComponent },
  { path: 'admin/dashboard', component: AdminDashboardComponent },
  { path: 'admin/roles', component: AdminRolesComponent },
  { path: 'admin/role/:slug/edit', component: AdminRolesComponent },
  { path: 'admin/permissions', component: AdminPermissionsComponent },
  { path: 'admin/permission/:slug/edit', component: AdminEditPermissionsComponent },
  { path: '', redirectTo: '/home', pathMatch: 'full' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }