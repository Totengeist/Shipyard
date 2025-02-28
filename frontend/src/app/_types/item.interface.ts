import { ScreenshotInterface } from '../_types/screenshot.interface';
import { UserInterface } from '../_types/user.interface';

export interface ItemInterface {
    ref: string,
    title: string|null,
    description: string|null
    downloads: number,
    user: UserInterface,
    flags: number
    parent?: ItemInterface,
    children?: ItemInterface[],
    tags?: any[],
    primary_screenshot: ScreenshotInterface[]
}
