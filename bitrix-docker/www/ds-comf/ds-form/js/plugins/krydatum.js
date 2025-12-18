

function kryDatum(field, opts){
            // setting date first time
            var currdate = new Date(),
               defaults = {
               udate: false,
                  format: '{DD}.{MM}.{YYYY}',
                  days: ['пн','вт','ср','чт','пт','сб','вс'],
                  months: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
                  months2: ['Января','Февраля','Марта','Апреля','Мая','Июня','Июля','Августа','Сентября','Октября','Ноября','Декабря'],
                  sundayFirst: false, // if true, Sunday starts as first day of the week, be sure that days array is changed accordingly!               
               };
            
            for (var opt in defaults) {
               if (opts[opt] === undefined) {
                  opts[opt] = defaults[opt];
               }      
            }
            
            
            if (opts.udate && typeof opts.udate === 'string'){
               opts.udate = opts.udate.split('.');
               var theYear = parseInt(opts.udate[2])
               var theMonth = parseInt(opts.udate[1]) - 1;
               var theDay = parseInt(opts.udate[0]);
               theDay = theDay == 0 ? currdate.getDate() : theDay;
               currdate = new Date(theYear,theMonth,theDay); 
            } else if (opts.udate && typeof opts.udate === 'number'){
               currdate = new Date(currdate.getFullYear(),currdate.getMonth() + opts.udate,currdate.getDate());
            }
            
            var obj = document.createElement('span');
            obj.className = 'dsform-cal-layer';
            var btn = document.createElement('span');
            btn.className = "dsform-cal-btn";
            field.parentElement.appendChild(btn);
            
            btn.addEventListener('click', function () {
               if (btn.className.indexOf("cal-chosen") > -1 ){
                  btn.className = btn.className.replace(/cal-chosen/g, '');
               } else {
                  btn.className = btn.className + ' cal-chosen';
               }
               
               if (obj.style.display == 'none') {
                  obj.style.display = 'inline';
               } else {
                  obj.style.display = 'none';
               }                  
            
            });
            
            obj.style.display = 'none';
            
            field.parentElement.appendChild(obj);
            
               
            function set(d) {
               d.day = opts.sundayFirst == true ? d.getDay() : d.getDay() == 0 ? 6 : d.getDay() - 1;
               d.date = d.getDate();
               d.month = d.getMonth();
               d.year = d.getFullYear();
               d.nextMonth = new Date(d.year, d.month + 1, d.date);
               d.prevMonth = new Date(d.year, d.month - 1, d.date);
               d.days = Math.ceil((d.nextMonth - d)/86400000);
               var first = new Date(d.year, d.month, 1);
               var last  = new Date(d.year, d.month, d.days);
               d.firstday = opts.sundayFirst == true ? first.getDay() : first.getDay() == 0 ? 6 : first.getDay() - 1;
               d.lastday  = opts.sundayFirst == true ? last.getDay() : last.getDay() == 0 ? 6 : last.getDay() - 1;
               currdate = d;
            } 
            
            var getP = function () {
               set(currdate.prevMonth);
               insert(obj);
            }
            
            var getN = function () {
               set(currdate.nextMonth);
               insert(obj);
            }
            
            var format = function (str, dd) {
               var DD = dd < 10 ? '0' + dd : dd;
               var D = dd;
               var M = currdate.month + 1;
               var MM = M < 10 ? '0' + M : M;
               var MMM = opts.months[currdate.month].slice(0,3).toLowerCase();
               var MMMM = opts.months[currdate.month];
               var MMMMM = opts.months2[currdate.month];
               var YY = String(currdate.year).slice(2,4);
               var YYYY = currdate.year;
               var r = str.replace(/{D}/g,D).replace(/{DD}/g,DD).replace(/{M}/g,M).replace(/{MM}/g,MM).replace(/{MMM}/g,MMM).replace(/{MMMM}/g,MMMM).replace(/{MMMMM}/g,MMMMM).replace(/{YY}/g,YY).replace(/{YYYY}/g,YYYY);
               return r;
            } 
                 
            var dayTD = function (tdo, date) {
                tdo.setAttribute('data-selected-date', format(opts.format, date));
                
                tdo.addEventListener('click', function () {
                  field.value = format(opts.format, date);
                  
                  if (btn.className.indexOf("cal-chosen") > -1 ){
                  btn.className = btn.className.replace(/cal-chosen/g, '');
                  } else {
                     btn.className = btn.className + ' cal-chosen';
                  }
                  
                  if (obj.style.display == 'none') {
                     obj.style.display = 'inline';
                  } else {
                     obj.style.display = 'none';
                  }     
                
                });
                
                return tdo;
            }
            
            var insert = function (){
               var table = document.createElement('table'),
               current = document.createElement('thead'),
               daystr = document.createElement('tr'),
               monthtr = document.createElement('tr'),
               monthtd = document.createElement('td'),
               weeks = document.createElement('tbody'), 
               daysarr = [];
               
               monthtd.setAttribute('colspan','7');
               monthtd.className = 'mth-hdr';
               var nm = document.createElement('span'),
               pm = document.createElement('span');
               
               nm.className = 'next-month month-btn';
               pm.className = 'prev-month month-btn';
               
               nm.addEventListener('click', getN);
               pm.addEventListener('click', getP);
               
               for (var n = 0; n < 7; n++) {
                  var wda = document.createElement('td');
                  wda.innerHTML = opts.days[n];
                  wda.setAttribute('class','wday wda-' + n);
                  daystr.appendChild(wda);
               }      
               
               monthtd.innerHTML = opts.months[currdate.month] + ' ' + currdate.year;
               monthtd.appendChild(pm);
               monthtd.appendChild(nm);
               
               monthtr.appendChild(monthtd);
               
               current.appendChild(monthtr);
               current.appendChild(daystr);
               
               for (var n = - currdate.firstday+1; n < 43; n++) {
                  daysarr.push(n);
               }
               for (i = 0; i < 7; i++) {
                var tr = document.createElement('tr');
                weeks.appendChild(tr);
               }
               // counting days
               for (var i = 0; i < 42; i++){
                  var date = daysarr[i] < 1 || daysarr[i] > currdate.days ? '&nbsp;' : daysarr[i];
                  var td = document.createElement('td');
                  if (daysarr[i] == currdate.date) td.className = 'crrnt-day';
                  if (date == '&nbsp;') td.className = 'nodate';
                  if (date != '&nbsp;') {
                     td = dayTD(td, date);
                  }
                  var week = Math.floor(i/7);
                  td.innerHTML = date;
                  weeks.children[week].appendChild(td);
               } 
               table.className = 'dscalendar-table'
               table.appendChild(current);
               table.appendChild(weeks); 
               obj.innerHTML = '';
               obj.appendChild(table);
            }
            
            set(currdate);
            insert(obj);
            
         } // attachCal ends 
