import { PermissionInterface } from '../_types/permission.interface';

export interface RoleInterface {
    slug: string,
    label: string,
    permissions?: PermissionInterface[],
}
