@php $analytics = $analytics ?? []; @endphp
@if (! empty($analytics['enabled']) && ! empty($analytics['measurement_id']))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $analytics['measurement_id'] }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($analytics['measurement_id']));
    </script>
@endif
@if (! empty($analytics['gtm_enabled']) && ! empty($analytics['gtm_id']))
    <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+encodeURIComponent(i)+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',@json($analytics['gtm_id']));
    </script>
@endif
@if (! empty($analytics['pixel_enabled']) && ! empty($analytics['meta_pixel_id']))
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', @json($analytics['meta_pixel_id']));
        fbq('track', 'PageView');
    </script>
@endif
