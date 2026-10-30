const CACHE='srms-static-v3';
const STATIC=['./','./index.php','./offline.html','./css/bootstrap.min.css','./css/srms-landing.css','./css/srms-responsive.css','./apple-touch-icon.png','./manifest.webmanifest'];
self.addEventListener('install',event=>{event.waitUntil(caches.open(CACHE).then(c=>c.addAll(STATIC)).then(()=>self.skipWaiting()));});
self.addEventListener('activate',event=>{event.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(k=>k!==CACHE).map(k=>caches.delete(k)))).then(()=>self.clients.claim()));});
self.addEventListener('fetch',event=>{
  const u=new URL(event.request.url);
  if(event.request.method!=='GET'||u.origin!==location.origin)return;
  if(event.request.mode==='navigate'){
    event.respondWith(fetch(event.request).catch(()=>caches.match(event.request).then(r=>r||caches.match('./offline.html'))));
    return;
  }
  if(STATIC.some(x=>u.pathname.endsWith(x.replace('./','')))){
    event.respondWith(fetch(event.request).then(r=>{
      if(r.ok){const copy=r.clone();caches.open(CACHE).then(c=>c.put(event.request,copy));}
      return r;
    }).catch(()=>caches.match(event.request)));
  }
});
