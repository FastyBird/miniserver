# FastyBird Web UI library

## What is FastyBird Web UI library?

This library is a collection of [Vue 3](https://vuejs.org) framework components used for [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) web applications user interfaces.

[FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) Web UI library is an [Apache2 licensed](http://www.apache.org/licenses/LICENSE-2.0) distributed library, developed in [Typescript](https://www.typescriptlang.org) on top of the [Vue](https://vuejs.org) framework.

### Features:

- Web user interface elements like modal windows, buttons, alerts, switches etc.
- Forms elements with support for pre and post-fixes
- Page layout components for desktop and mobile views
- Customizable styles

## Requirements

Library is tested against [ECMAScript 6](https://www.w3schools.com/JS/js_es6.asp)

## Installation

The best way to install **@fastybird/web-ui-library** is using [Yarn](https://yarnpkg.com/):

```sh
yarn add @fastybird/web-ui-library
```

or if you prefer npm:

```sh
npm install @fastybird/web-ui-library
```

## Setup in your application

Register Vue plugin:

```js
import { createApp } from 'vue';
import { createWebUiTheme } from '@fastybird/web-ui-library'

import App from '@/App.vue';

const app = createApp(App);

app.use(createWebUiTheme());
```

## Usage

In you component you could use registered visual, form and layouts components:

```vue
<template>
    <div>
        Your fancy content here

        <fb-ui-button
          href="/new/page"
          variant="primary"
          size="md"
        >
            Click here
        </fb-ui-button>
    </div>
</template>
```

## Typescript setup

Add the types to your `"types"` array in **tsconfig.json**.

```json
{
  "compilerOptions": {
    "types": [
      "@fastybird/web-ui-library"
    ]
  }
}
```

## Documentation

Learn how to use ui components in [documentation](https://storybook.fastybird.com/).

## Feedback

Use the [issue tracker](https://github.com/FastyBird/fastybird/issues) for bugs
or [mail](mailto:code@fastybird.com) or [Tweet](https://twitter.com/fastybird) us for any idea that can improve the
project.

Thank you for testing, reporting and contributing.

## Changelog

For release info check [release page](https://github.com/FastyBird/fastybird/releases).

## Contribute

The sources of this package are contained in the [FastyBird monorepo](https://github.com/FastyBird/fastybird). We welcome contributions for this package on [FastyBird/fastybird](https://github.com/FastyBird/).

## Maintainers

<table>
	<tbody>
		<tr>
			<td align="center">
				<a href="https://github.com/akadlec">
					<img alt="akadlec" width="80" height="80" src="https://avatars3.githubusercontent.com/u/1866672?s=460&amp;v=4" />
				</a>
				<br>
				<a href="https://github.com/akadlec">Adam Kadlec</a>
			</td>
		</tr>
	</tbody>
</table>

***
Homepage [https://www.fastybird.com](https://www.fastybird.com) and repository [https://github.com/FastyBird/web-ui-library](https://github.com/FastyBird/web-ui-library).
