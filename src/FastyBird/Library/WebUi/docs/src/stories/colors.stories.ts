import { Story } from '@storybook/vue3';

import './colors.scss';

export default {
	title: 'About/Colors',
};

export const Colors: Story = () => {
	return {
		template: `
      <div class="fb-story-colors__container">
        <div class="fb-story-colors__colors">
          <div class="fb-story-colors__colors-item fb-story-colors__colors-item-primary"><span class="fb-story-colors__colors-item-desc">primary</span></div>
          <div class="fb-story-colors__colors-item fb-story-colors__colors-item-success"><span class="fb-story-colors__colors-item-desc">success</span></div>
          <div class="fb-story-colors__colors-item fb-story-colors__colors-item-info"><span class="fb-story-colors__colors-item-desc">info</span></div>
          <div class="fb-story-colors__colors-item fb-story-colors__colors-item-warning"><span class="fb-story-colors__colors-item-desc">warning</span></div>
          <div class="fb-story-colors__colors-item fb-story-colors__colors-item-danger"><span class="fb-story-colors__colors-item-desc">danger</span></div>
          <div class="fb-story-colors__colors-item fb-story-colors__colors-item-error"><span class="fb-story-colors__colors-item-desc">error</span></div>
          <div class="fb-story-colors__colors-item fb-story-colors__colors-item-default"><span class="fb-story-colors__colors-item-desc">default</span></div>
        </div>
      </div>
    `,
	};
};
