import register from 'ShopUi/app/registry';
export default register(
    'captcha-altcha',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "captcha-altcha" */
            './captcha-altcha'
        ),
);
