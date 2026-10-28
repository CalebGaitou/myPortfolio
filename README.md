# Caleb Gaïtou — Portfolio

My academic portfolio for the Information Technology programme at NHL Stenden University of Applied Sciences, Emmen.

**Live site:** https://calebgaitou.github.io/myPortfolio/

Plain HTML, CSS and a little JavaScript, hosted on GitHub Pages. No PHP and no database.

## Structure

```
index.html          redirects to Portfolio/index.html
404.html            shown by GitHub Pages for missing pages
Portfolio/
  index.html        home (library)
  about.html        about me, education, skills, contact
  year1.html        year 1 coursework
  year2.html        year 2 coursework
  calendar.html     schedule (Outlook embed)
  Css/              styles
  js/search.js      page search
  Files/            coursework files, by year and module
```

## Local preview

Open `Portfolio/index.html` in a browser, or from this folder run:

```bash
npx --yes serve .
```

Then visit the URL it prints.

## Publish on GitHub Pages

1. In the repo: **Settings → Pages**.
2. Source: **Deploy from a branch**.
3. Branch: `static`, folder: `/ (root)`.
4. Save. After a minute the site is live at https://calebgaitou.github.io/myPortfolio/

## Adding coursework

1. Put the file in `Portfolio/Files/<Year>/<Module>/` with a readable name.
2. Add a row to the table in `year1.html` or `year2.html`.
3. Commit and push. Pages updates automatically.
