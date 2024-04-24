import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ModificationsComponent } from './modifications.component';

describe('ModificationsComponent', () => {
  let component: ModificationsComponent;
  let fixture: ComponentFixture<ModificationsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ModificationsComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ModificationsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
