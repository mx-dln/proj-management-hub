class ProjectExplorer {
 constructor(root) {
  this.root=root; this.endpoint=root.dataset.endpoint; this.csrf=root.dataset.csrf;
  this.id=Number(new URL(location.href).searchParams.get('folder')||0);
  this.mode=localStorage.getItem('projectExplorerView')||'grid'; this.search=''; this.serial=0;
  this.trail=history.state?.explorer?.trail||[this.id]; this.position=history.state?.explorer?.position||0;
  this.queue=[]; this.queueSeq=0; this.queueRunning=false; this.folderCache=new Map(); this.busyCount=0;
  this.$=id=>document.getElementById(`pd-${id}`);
 }
 async request(action,params={},body=null,progress=null) {
  if(body) return this.xhr(action,params,body,progress);
  const r=await fetch(`${this.endpoint}?${new URLSearchParams({action,...params})}`,{headers:{'X-Requested-With':'XMLHttpRequest'}});
  let d; try {d=await r.json();} catch {throw new Error('Unable to load Explorer. Refresh and try again.');}
  if(!r.ok||!d.success) throw new Error(d.message||'Request failed'); return d;
 }
 xhr(action,params,body,progress=null) {
  return new Promise((resolve,reject)=>{
   const xhr=new XMLHttpRequest(); xhr.open('POST',`${this.endpoint}?${new URLSearchParams({action,...params})}`);
   xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
   if(progress) xhr.upload.onprogress=e=>{if(e.lengthComputable)progress(Math.round((e.loaded/e.total)*100));};
   xhr.onload=()=>{let d;try{d=JSON.parse(xhr.responseText);}catch{reject(new Error('The server rejected this upload before Drive could process it. Check post_max_size, upload_max_filesize, and restart EnvKit.'));return;}
    if(xhr.status<200||xhr.status>=300||!d.success) reject(new Error(d.message||'Request failed')); else resolve(d);};
   xhr.onerror=()=>reject(new Error('Network error while contacting Explorer.')); xhr.send(body);
  });
 }
 init() {
  this.$('grid').onclick=()=>this.view('grid'); this.$('list').onclick=()=>this.view('list');
  this.$('new').onclick=()=>{this.$('new-menu').hidden=!this.$('new-menu').hidden;};
  this.$('upload').onclick=()=>this.$('files').click();
  this.$('files').onchange=e=>{this.enqueueFiles(e.target.files,this.id);e.target.value='';};
  this.$('folder-files').onchange=e=>{this.enqueueFiles(e.target.files,this.id);e.target.value='';};
  this.$('queue-min').onclick=()=>this.$('queue').classList.toggle('pd-queue-compact');
  this.$('search').oninput=e=>{clearTimeout(this.timer);this.search=e.target.value;this.timer=setTimeout(()=>this.load(),250);};
  this.$('back').onclick=()=>{if(this.position>0)history.back();};
  this.$('forward').onclick=()=>{if(this.position<this.trail.length-1)history.forward();};
  window.addEventListener('popstate',e=>{this.position=e.state?.explorer?.position||0;this.id=Number(new URL(location.href).searchParams.get('folder')||0);this.search='';this.$('search').value='';history.replaceState({...history.state,explorer:{trail:this.trail,position:this.position}},'');this.load();});
  window.addEventListener('beforeunload',e=>{if(this.queueRunning){e.preventDefault();e.returnValue='';}});
  document.addEventListener('click',e=>{if(!e.target.closest('.pd-new-wrap'))this.$('new-menu').hidden=true;if(!e.target.closest('#pd-context')&&!e.target.closest('.pd-more'))this.$('context').hidden=true;});
  document.addEventListener('keydown',e=>{if(e.key==='Escape'){this.$('context').hidden=true;this.$('new-menu').hidden=true;}});
  this.$('close').onclick=this.$('cancel').onclick=()=>this.$('dialog').close();
  this.$('items').ondragover=e=>{e.preventDefault();this.$('items').classList.add('pd-drop');};
  this.$('items').ondragleave=e=>{if(!this.$('items').contains(e.relatedTarget))this.$('items').classList.remove('pd-drop');};
  this.$('items').ondrop=e=>this.drop(e,this.id);
  this.load();
 }
 busy(label,on=true) {
  this.busyCount+=on?1:-1; if(this.busyCount<0)this.busyCount=0;
  const box=this.$('busy'); box.hidden=this.busyCount===0; box.querySelector('span').textContent=label||'Working...';
  this.root.classList.toggle('pd-working',this.busyCount>0);
 }
 async withBusy(label,fn) {this.busy(label,true);try{return await fn();}finally{this.busy(label,false);}}
 async navigate(id,add=true) {
  this.id=Number(id);this.search='';this.$('search').value='';
  if(add){this.trail=this.trail.slice(0,this.position+1);this.trail.push(this.id);history.replaceState({...history.state,explorer:{trail:this.trail,position:this.position}},'');this.position++;}
  const url=new URL(location.href);url.searchParams.set('folder',this.id);history.pushState({explorer:{trail:this.trail,position:this.position}},'',url);
  this.$('context').hidden=true;await this.load();
 }
 async load() {
  const serial=++this.serial;this.status('Loading...');
  try{const data=await this.request('list',{id:this.id,q:this.search});if(serial!==this.serial)return;this.data=data;this.render();this.status('');}
  catch(e){if(serial===this.serial){this.$('items').replaceChildren();this.$('breadcrumbs').replaceChildren(this.button('My Drive','hard-drive',()=>this.navigate(0)));this.$('new').hidden=true;this.$('upload').hidden=true;this.status(e.message,true);}}
 }
 status(text,error=false){this.$('status').textContent=text;this.$('status').classList.toggle('pd-error',error);}
 button(label,icon,handler,cls='') {const b=document.createElement('button');b.type='button';b.className=cls;if(icon){const i=document.createElement('i');i.className=`fas fa-${icon}`;b.append(i);}if(label)b.append(document.createTextNode(label));b.onclick=handler;return b;}
 view(mode){this.mode=mode;localStorage.setItem('projectExplorerView',mode);if(this.data)this.render();}
 fileUrl(n,download=false){return `${this.endpoint}?action=file&id=${n.id}${download?'&download=1':''}`;}
 icon(n){if(n.kind!=='file')return 'folder';const e=n.extension;for(const [list,icon] of [[['jpg','jpeg','png','gif','webp'],'file-image'],[['pdf'],'file-pdf'],[['doc','docx'],'file-word'],[['xls','xlsx','csv'],'file-excel'],[['ppt','pptx'],'file-powerpoint'],[['mp4','webm','mov'],'file-video'],[['mp3','wav'],'file-audio'],[['zip'],'file-zipper']])if(list.includes(e))return icon;return 'file-lines';}
 size(b){if(b==null)return '';if(b<1024)return `${b} B`;const u=b<1048576?1024:1048576;return `${(b/u).toFixed(1)} ${u===1024?'KB':'MB'}`;}
 date(v){return v?new Date(v.replace(' ','T')).toLocaleDateString(undefined,{year:'numeric',month:'short',day:'numeric'}):'';}
 type(n){if(n.kind==='file')return `${(n.extension||'file').toUpperCase()} file`;return ({component_group:'System folder',activity_group:'System folder'}[n.kind]||n.kind[0].toUpperCase()+n.kind.slice(1));}
 render(){
  const d=this.data;this.$('count').textContent=`${d.items.length} item${d.items.length===1?'':'s'}`;
  this.$('back').disabled=this.position===0;this.$('forward').disabled=this.position>=this.trail.length-1;
  const crumbs=this.$('breadcrumbs');crumbs.replaceChildren(this.button('My Drive','hard-drive',()=>this.navigate(0)));
  for(const c of d.breadcrumbs){const i=document.createElement('i');i.className='fas fa-chevron-right';const b=this.button(c.name,null,()=>this.navigate(c.id));b.title=c.name;if(Number(c.id)===this.id)b.setAttribute('aria-current','page');crumbs.append(i,b);}
  requestAnimationFrame(()=>{crumbs.scrollLeft=crumbs.scrollWidth;});
  const limit=this.$('limit');limit.hidden=!d.upload||!d.storage; if(d.upload&&d.storage)limit.textContent=`Upload limit: ${this.size(d.storage.effective_upload_limit_bytes)} per file. Drive uses ${this.size(d.storage.used_bytes)}. Hosting free space: ${this.size(d.storage.disk_free_bytes)}.`;
  this.$('new').hidden=!((d.create&&d.create.length)||d.upload);this.$('upload').hidden=!d.upload;this.$('new-menu').replaceChildren();
  for(const option of d.create||[])this.$('new-menu').append(this.button(option.label.replace('_',' '),'folder-plus',()=>this.create(option)));
  if(d.upload){this.$('new-menu').append(this.button('Upload file','upload',()=>this.$('files').click()));this.$('new-menu').append(this.button('Upload folder','folder-arrow-up',()=>this.$('folder-files').click()));}
  this.$('grid').setAttribute('aria-pressed',this.mode==='grid');this.$('list').setAttribute('aria-pressed',this.mode==='list');
  const container=this.$('items');container.className=`pd-${this.mode}`;container.replaceChildren();
  if(!d.items.length){const empty=document.createElement('div');empty.className='pd-empty';const i=document.createElement('i');i.className='fas fa-folder-open';const p=document.createElement('p');p.textContent=this.search?'No matching items':'This folder is empty';empty.append(i,p);container.append(empty);return;}
  if(this.mode==='list'){const row=document.createElement('div');row.className='pd-list-head';for(const l of ['Name','Owner','Modified','File Size','Type','']){const s=document.createElement('span');s.textContent=l;row.append(s);}container.append(row);}
  for(const n of d.items){
   const card=document.createElement('div');card.className='pd-item';card.tabIndex=0;card.setAttribute('role','group');card.setAttribute('aria-label',n.name);
   card.ondblclick=()=>this.open(n);card.onkeydown=e=>{if(e.key==='Enter'&&e.target===card)this.open(n);};
   card.onclick=()=>{container.querySelectorAll('.pd-selected').forEach(e=>e.classList.remove('pd-selected'));card.classList.add('pd-selected');};
   card.oncontextmenu=e=>{e.preventDefault();this.menu(n,e.clientX,e.clientY);};card.draggable=n.writable;
   card.ondragstart=e=>e.dataTransfer.setData('text/x-explorer-id',n.id);
   if(n.kind!=='file'){
    card.ondragover=e=>{e.preventDefault();card.classList.add('pd-drop');};card.ondragleave=()=>card.classList.remove('pd-drop');
    card.ondrop=e=>{e.preventDefault();e.stopPropagation();card.classList.remove('pd-drop');this.$('items').classList.remove('pd-drop');if(e.dataTransfer.files.length)return this.drop(e,Number(n.id));const source=e.dataTransfer.getData('text/x-explorer-id');if(source)void this.change('move',{id:source,parent_id:n.id});};
   }
   const name=document.createElement('div');name.className='pd-name';const i=document.createElement('i');i.className=`fas fa-${this.icon(n)} pd-icon ${n.kind==='file'?'pd-file-icon':''}`;
   const text=document.createElement('span');text.textContent=n.name;text.title=n.name;name.append(i,text);card.append(name);
   if(this.mode==='grid'){const visual=document.createElement('div');visual.className=`pd-visual ${n.kind!=='file'?'pd-folder-visual':''}`;
    if(['image/jpeg','image/png','image/gif','image/webp'].includes(n.mime_type)){const img=document.createElement('img');img.src=this.fileUrl(n);img.alt=n.name;img.loading='lazy';visual.append(img);}else visual.append(i.cloneNode());card.prepend(visual);
    const meta=document.createElement('small');meta.textContent=this.search?n.location:this.type(n);card.append(meta);
   }else for(const value of [n.owner,this.date(n.updated_at),this.size(n.size),this.type(n)]){const s=document.createElement('span');s.className='pd-cell';s.textContent=value||'';card.append(s);}
   const more=this.button('','ellipsis-vertical',e=>{e.stopPropagation();const r=more.getBoundingClientRect();this.menu(n,r.right,r.bottom);},'pd-more');more.title=`Actions for ${n.name}`;more.setAttribute('aria-label',more.title);card.append(more);container.append(card);
  }
 }
 menu(n,x,y){const m=this.$('context');m.replaceChildren();const add=(t,i,f)=>m.append(this.button(t,i,()=>{m.hidden=true;f();}));add(n.kind==='file'?'Open / Preview':'Open','folder-open',()=>this.open(n));if(n.kind==='file')add('Download','download',()=>this.download(n));if(n.writable){add('Rename','pen',()=>this.rename(n));add('Move','folder-tree',()=>this.move(n));add('Delete','trash',()=>this.remove(n));}add('Details','circle-info',()=>this.details(n));m.hidden=false;m.style.left=`${Math.max(8,Math.min(x,innerWidth-220))}px`;m.style.top=`${Math.max(8,Math.min(y,innerHeight-m.offsetHeight-8))}px`;m.querySelector('button').focus();}
 open(n){if(n.kind!=='file')this.navigate(n.id);else this.details(n,true);}
 download(n){const a=document.createElement('a');a.href=this.fileUrl(n,true);a.click();}
 dialog(title,submit='Save'){this.$('dialog-title').textContent=title;this.$('dialog-body').replaceChildren();this.$('dialog-error').textContent='';this.$('dialog-footer').hidden=!submit;this.$('submit').textContent=submit||'Save';this.$('submit').disabled=false;this.$('dialog').classList.remove('pd-preview','pd-details');this.$('form').onsubmit=e=>e.preventDefault();this.$('new-menu').hidden=true;this.$('dialog').showModal();}
 nameInput(value=''){const label=document.createElement('label');label.textContent='Name';const input=document.createElement('input');input.name='name';input.required=true;input.maxLength=255;input.value=value;label.append(input);this.$('dialog-body').append(label);input.focus();input.select();return input;}
 submit(handler,label='Saving...'){this.$('form').onsubmit=async e=>{e.preventDefault();this.$('submit').disabled=true;try{await this.withBusy(label,handler);this.$('dialog').close();await this.load();}catch(error){this.$('dialog-error').textContent=error.message;}finally{this.$('submit').disabled=false;}};}
 async post(action,values,progress=null){const body=new FormData();body.set('csrf',this.csrf);for(const [k,v]of Object.entries(values))body.set(k,v);return this.request(action,{},body,progress);}
 async change(action,values){try{await this.withBusy(`${action[0].toUpperCase()+action.slice(1)}...`,()=>this.post(action,values));await this.load();}catch(e){this.status(e.message,true);}}
 create(option=null){option=option||{label:`New ${this.data.next.replace('_',' ')}`,parent_id:this.id};this.dialog(option.label.replace('_',' '),'Create');const input=this.nameInput();this.submit(()=>this.post('create',{parent_id:option.parent_id,name:input.value}),'Creating...');}
 rename(n){this.dialog('Rename');const input=this.nameInput(n.name);this.submit(()=>this.post('rename',{id:n.id,name:input.value}),'Renaming...');}
 remove(n){this.dialog(`Delete ${n.name}?`,'Delete');const p=document.createElement('p');p.textContent=n.kind==='file'?'This file will be removed from Explorer.':'This folder and all its contents will be removed from Explorer.';this.$('dialog-body').append(p);this.submit(()=>this.post('delete',{id:n.id}),'Deleting...');}
 async move(n){
  this.dialog(`Move ${n.name}`,'Move here');let destination=Number(n.parent_id||0);let requestId=0;
  const browse=async id=>{const serial=++requestId;this.$('submit').disabled=true;try{const d=await this.request('list',{id});if(serial!==requestId)return;destination=Number(id);const b=this.$('dialog-body');b.replaceChildren();const nav=document.createElement('div');nav.className='pd-move-path';nav.append(this.button('My Drive','hard-drive',()=>browse(0)));for(const p of d.breadcrumbs)nav.append(this.button(p.name,'chevron-right',()=>browse(p.id)));b.append(nav);for(const item of d.items.filter(i=>i.kind!=='file'&&i.id!==n.id))b.append(this.button(item.name,'folder',()=>browse(item.id),'pd-destination'));this.$('submit').disabled=!d.writable||(n.kind==='file'?!['component','activity','folder'].includes(d.current.kind):d.next!==n.kind)||d.breadcrumbs.some(i=>i.id===n.id);}catch(e){this.$('dialog-error').textContent=e.message;}};
  this.submit(()=>this.post('move',{id:n.id,parent_id:destination}),'Moving...');await browse(destination);
 }
 async details(n,preview=false){
  this.dialog(preview?n.name:'Details',null); if(!preview)this.$('dialog').classList.add('pd-details');
  try{const d=await this.withBusy('Loading details...',()=>this.request('details',{id:n.id}));n=d.item;if(!this.$('dialog').open)return;const b=this.$('dialog-body');
   if(preview){this.$('dialog').classList.add('pd-preview');let media;if(['image/jpeg','image/png','image/gif','image/webp'].includes(n.mime_type)){media=document.createElement('img');media.alt=n.name;}else if(['video/mp4','video/webm'].includes(n.mime_type)){media=document.createElement('video');media.controls=true;}else if(['audio/mpeg','audio/wav'].includes(n.mime_type)){media=document.createElement('audio');media.controls=true;}else if(['application/pdf','text/plain'].includes(n.mime_type)){media=document.createElement('iframe');media.title=n.name;}if(media){media.src=this.fileUrl(n);media.className='pd-media';b.append(media);}}
   const dl=document.createElement('dl');const fields={Name:n.name,Type:this.type(n),Location:n.location,Owner:n.owner,Created:this.date(n.created_at),Modified:this.date(n.updated_at)};
   if(n.kind==='file'){fields.Size=this.size(n.size);fields['MIME type']=n.mime_type;}else fields['Number of items']=n.item_count;
   for(const [key,value]of Object.entries(fields))if(value!==null&&value!==''){const dt=document.createElement('dt');dt.textContent=key;const dd=document.createElement('dd');dd.textContent=value;dl.append(dt,dd);}b.append(dl);
   if(n.kind==='file'){b.append(this.button('Download','download',()=>this.download(n),'btn-primary'));if(!preview&&['image/jpeg','image/png','image/gif','image/webp','application/pdf','text/plain','video/mp4','video/webm','audio/mpeg','audio/wav'].includes(n.mime_type))b.append(this.button('Preview','eye',()=>{this.$('dialog').close();this.details(n,true);},'btn-ghost'));}
  }catch(e){this.$('dialog-error').textContent=e.message;}
 }
 async drop(e,parent=this.id){e.preventDefault();this.$('items').classList.remove('pd-drop');try{const entries=await this.collectDrop(e.dataTransfer);this.addUploads(entries,parent);}catch(error){this.status(error.message,true);}}
 enqueueFiles(files,parent=this.id){this.addUploads(Array.from(files).map(file=>({file,path:[]})),parent);}
 async collectDrop(dataTransfer) {
  const items=Array.from(dataTransfer.items||[]).filter(item=>item.kind==='file');
  if(!items.length) return Array.from(dataTransfer.files||[]).map(file=>({file,path:this.pathFromFile(file)}));
  const collected=[];
  for(const item of items){const entry=item.webkitGetAsEntry?.();if(entry) await this.walkEntry(entry,[],collected);else {const file=item.getAsFile();if(file)collected.push({file,path:this.pathFromFile(file)});}}
  return collected;
 }
 pathFromFile(file){const parts=(file.webkitRelativePath||'').split('/').filter(Boolean);parts.pop();return parts;}
 walkEntry(entry,path,out){
  return new Promise((resolve,reject)=>{
   if(entry.isFile){entry.file(file=>{out.push({file,path});resolve();},reject);return;}
   if(!entry.isDirectory){resolve();return;}
   const next=[...path,entry.name], reader=entry.createReader(), tasks=[];
   const read=()=>reader.readEntries(entries=>{if(!entries.length){Promise.all(tasks).then(resolve,reject);return;}for(const child of entries)tasks.push(this.walkEntry(child,next,out));read();},reject);
   read();
  });
 }
 addUploads(entries,parent) {
  if(!entries.length)return; if(parent===this.id&&this.data&&!this.data.upload){this.status('Open a component, activity, or folder to upload files.',true);return;}
  for(const entry of entries){this.queue.push({id:++this.queueSeq,parent,file:entry.file,path:entry.path||[],name:entry.file.name,status:'queued',progress:0,error:''});}
  this.renderQueue(); this.processQueue();
 }
 renderQueue() {
  const panel=this.$('queue'), list=this.$('queue-list'); panel.hidden=this.queue.length===0; list.replaceChildren();
  const active=this.queue.filter(q=>['queued','uploading','preparing'].includes(q.status)).length;
  this.$('queue-title').textContent=active?`Uploading ${active} item${active===1?'':'s'}`:'Uploads complete';
  for(const item of this.queue.slice(-8)){
   const row=document.createElement('div');row.className=`pd-queue-row pd-${item.status}`;
   const icon=document.createElement('i');icon.className=`fas fa-${item.status==='done'?'check-circle':item.status==='error'?'circle-exclamation':'cloud-arrow-up'}`;
   const body=document.createElement('div'), name=document.createElement('strong'), meta=document.createElement('span'), bar=document.createElement('b');
   name.textContent=item.path.length?[...item.path,item.name].join('/'):item.name;
   meta.textContent=item.error||({queued:'Queued',preparing:'Preparing folders',uploading:`${item.progress}%`,done:'Uploaded'}[item.status]||item.status);
   bar.style.width=`${item.status==='done'?100:item.progress}%`;body.append(name,meta,bar);row.append(icon,body);list.append(row);
  }
 }
 async processQueue() {
  if(this.queueRunning)return; this.queueRunning=true; this.busy('Uploading...',true);
  try{
   while(true){const item=this.queue.find(q=>q.status==='queued');if(!item)break;
    try{item.status='preparing';this.renderQueue();const parent=await this.ensurePath(item.parent,item.path);item.status='uploading';item.progress=1;this.renderQueue();await this.post('upload',{parent_id:parent,file:item.file},p=>{item.progress=p;this.renderQueue();});item.status='done';item.progress=100;this.folderCache.clear();if(parent===this.id||item.parent===this.id)await this.load();}
    catch(e){item.status='error';item.error=e.message;}
    this.renderQueue();
   }
  } finally {this.queueRunning=false;this.busy('Uploading...',false);if(this.queue.some(q=>q.status==='done'))setTimeout(()=>{if(!this.queueRunning){this.queue=this.queue.filter(q=>q.status!=='done');this.renderQueue();}},4000);}
 }
 async ensurePath(parent,path) {
  let id=parent; for(const raw of path){const name=raw.trim();if(!name)continue;const key=`${id}/${name.toLowerCase()}`;if(this.folderCache.has(key)){id=this.folderCache.get(key);continue;}
   const listing=await this.request('list',{id});let folder=listing.items.find(item=>item.kind!=='file'&&item.name.toLowerCase()===name.toLowerCase());
   if(!folder){folder=await this.withBusy('Creating upload folders...',()=>this.post('create',{parent_id:id,name}));folder={id:folder.id};}
   id=Number(folder.id);this.folderCache.set(key,id);
  } return id;
 }
}
document.addEventListener('DOMContentLoaded',()=>{const root=document.getElementById('project-drive');if(root){window.explorer=new ProjectExplorer(root);window.explorer.init();}});
