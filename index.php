<?php

require 'vendor/autoload.php';

use Carbon\Carbon;

$days = [
  [
    "day" => new Carbon("october 4"),
    "title" => "Kanelbullens dag",
    "link" => "https://sv.wikipedia.org/wiki/Kanelbullens_dag",
    "image" => "https://res.cloudinary.com/hufennija/image/upload/q_auto,f_auto,fl_progressive,c_lfill/2367529.jpg"
  ],
  [
    "day" => Carbon::createFromTimestamp(easter_date())->sub("47 days"),
    "title" => "Fettisdagen :semla:",
    "link" => "https://sv.wikipedia.org/wiki/Fettisdagen",
    "image" => "https://cdn.valio.fi/mediafiles/f0ca373f-3641-483b-83ef-88f2d2bad34c/1440x1080-recipe-hero/4x3/klassisk-laktosfri-semla.jpg",
  ],
  [
    "day" => new Carbon("first thursday of march"),
    "title" => "Fössta tossdan i mass",
    "link" => "https://sv.wikipedia.org/wiki/F%C3%B6ssta_tossdan_i_mass",
    "image" => "https://upload.wikimedia.org/wikipedia/commons/thumb/3/30/Sm%C3%A5lands_nationaldag.jpg/1920px-Sm%C3%A5lands_nationaldag.jpg",
  ],
  [
    "day" => new Carbon("february 5"),
    "title" => "Runebergsdagen",
    "link" => "https://sv.wikipedia.org/wiki/Runebergst%C3%A5rta",
    "image" => "https://files-aller-blogger-platform.aws.aller.com/uploads/sites/87/2022/04/runebergstarta-recept-2-700x454.jpg?w=700&fit=max&auto=format%201x",
  ],
  [
    "day" => new Carbon("7 march"),
    "title" => "Punschrullens dag :punschrulle:",
    "link" => "https://sv.wikipedia.org/wiki/Dammsugare_(bakverk)",
    "image" => "https://www.torebrings.se/bilder/artikelbilder/16/16011mega.jpg",
  ],
  [
    "day" => new Carbon("15 march"),
    "title" => "Kristofferdagen",
    "link" => "https://sv.wikipedia.org/wiki/Polhemsbakelse",
    "image" => "https://dms-api.ntm.eu/api/v1/images/jp5m3v6r/smart/width/980/height/551/as/jpeg",
  ],
  [
    "day" => new Carbon("25 march"),
    "title" => "Jungfru Maria bebådelsedag :waffle:",
    "link" => "https://sv.wikipedia.org/wiki/Jungfru_Marie_beb%C3%A5delsedag",
    "image" => "https://res.cloudinary.com/coopsverige/images/w_1200,h_1200/v1610649402/417035/V%C3%A5fflor+utan+%C3%A4gg.jpg",
  ],
  [
    "day" => new Carbon("1 may"),
    "title" => "Budapestbakelsens dag",
    "link" => "https://sv.wikipedia.org/wiki/Budapestbakelse",
    "image" => "https://www.bakemyday.se/wp-content/uploads/2018/10/2866.png",
  ],
  [
    "day" => new Carbon("second wednesday of may"),
    "title" => "Syltkakans dag",
    "link" => "https://sv.wikipedia.org/wiki/Hallongrotta",
    "image" => "https://usercontent.one/wp/www.lyckasmedbakning.nu/wp-content/uploads/2017/05/Syltkakor-syltsnittar.jpg",
  ],
  [
    "day" => new Carbon("6 june"),
    "title" => "Nationaldagsbakelse :flag-union:",
    "link" => "https://sv.wikipedia.org/wiki/Nationaldagsbakelse",
    "image" => "http://d2ihp3fq52ho68.cloudfront.net/YTo2OntzOjI6ImlkIjtpOjEzMDMwMDE7czoxOiJ3IjtpOjEwMjA7czoxOiJoIjtpOjMyMDA7czoxOiJjIjtpOjA7czoxOiJzIjtpOjA7czoxOiJrIjtzOjQwOiIxYjU3NzdiOGRiYTRjYjA1YTVhZjE2M2MwZWIzZmI5ODc4MGMyYTc2Ijt9"
  ],
  [
    "day" => new Carbon("last monday of september"),
    "title" => "Prinsesstårtans vecka",
    "link" => "https://sv.wikipedia.org/wiki/Prinsesst%C3%A5rta",
    "image" => "https://shared.cdn.smp.schibsted.com/v2/images/a2da274e-e4ef-44d3-a772-9860c59ffcaa?fit=crop&h=1425&w=1900&s=b72122d42b58f4045e8c2b74aced479715c53d8b"
  ],
  [
    "day" => new Carbon("first sunday of october"),
    "title" => "Gräddtårtans dag :strawberry:",
    "link" => "https://sv.wikipedia.org/wiki/Gr%C3%A4ddt%C3%A5rta",
    "image" => "https://i0.wp.com/media.zeinaskitchen.se/2016/04/08042016-IMG_4958-e1487070756944.jpg?fit=2000%2C1333&ssl=1"
  ],
  [
    "day" => new Carbon("14 october"),
    "title" => "Räkmackans dag :shrimp:",
    "link" => "https://sv.wikipedia.org/wiki/R%C3%A4ksm%C3%B6rg%C3%A5s",
    "image" => "https://res.cloudinary.com/coopsverige/images/w_1200,h_1200/v1566997026/380073/R%C3%A4kmacka.jpg"
  ],
  [
    "day" => new Carbon("20 october"),
    "title" => "Finska pinnens dag :flag-fi:",
    "link" => "https://sv.wikipedia.org/wiki/Finska_pinnar",
    "image" => "https://images.recept.se/images/recipes/finska-pinnar-med-smak-av-bittermandel_23197.jpg?fit=crop&crop=focalpoint&auto=format&fp-x=0.50037668876352&fp-y=0.51275960934352&fp-z=1.0224387536154&w=1200&h=628"
  ],
  [
    "day" => new Carbon("24 october"),
    "title" => "FN-bakelse :flag-un:",
    "link" => "https://sv.wikipedia.org/wiki/FN-bakelse",
    "image" => "https://dst15js82dk7j.cloudfront.net/234186/74095187-Jd85r.jpg"
  ],
  [
    "day" => new Carbon("6 november"),
    "title" => "Gustav Adolfs dödsdag",
    "link" => "https://sv.wikipedia.org/wiki/Gustav_Adolfsbakelse",
    "image" => "https://cached-images.bonnier.news/gcs/bilder/epi-30-dn/Images/2011/11/05/gustav-683.jpg"
  ],
  [
    "day" => new Carbon("7 november"),
    "title" => "Kladdkakans dag",
    "link" => "https://sv.wikipedia.org/wiki/Kladdkaka",
    "image" => "https://assets.icanet.se/t_ICAseAbsoluteUrl/imagevaultfiles/id_221660/cf_259/kladdig_kladdkaka.jpg"
  ],
  [
    "day" => new Carbon("13 november"),
    "title" => "Smörgåstårtans dag :heart:",
    "link" => "https://sv.wikipedia.org/wiki/Sm%C3%B6rg%C3%A5st%C3%A5rta",
    "image" => "https://images.recept.se/images/recipes/festlig-raktarta-med-polarbrod_7949.jpg?fit=crop&crop=focalpoint&auto=format&fp-x=0.5&fp-y=0.58863806555395&fp-z=1&w=800&h=570&auto=format"
  ],
  [
    "day" => new Carbon("14 november"),
    "title" => "Ostkakans dag",
    "link" => "https://sv.wikipedia.org/wiki/Ostkaka",
    "image" => "http://1.bp.blogspot.com/-NQDqu--2rOQ/VHNMmvt112I/AAAAAAAADIk/T3WrEdkfaZ0/s1600/Tacotallrik%2C%2Bostkaka%2Boch%2Bbudapest%2B052%2B(2).jpg"
  ],
  [
    "day" => new Carbon("13 december"),
    "title" => "Lussekattsdagen",
    "link" => "https://sv.wikipedia.org/wiki/Lussekatt",
    "image" => "https://images.recept.se/images/recipes/vara-godaste-lussekatter-med-smor_6992.jpg?fit=crop&crop=focalpoint&auto=format&fp-x=0.5&fp-y=0.46765511080213&fp-z=1&w=1200&h=628"
  ],
];

$now = new Carbon('now');
foreach($days as $idx => $daydata) {
  if($now->format("Y-m-d") == $daydata["day"]->format("Y-m-d")) {
    $webhook_url = "https://hooks.slack.com/services/<REDACTED>";
    $json = <<<EOT
{
        "blocks": [
                {
                        "type": "section",
                        "text": {
                                "type": "mrkdwn",
                                "text": ":warning: Idag är det *%s* - %s"
                        }
                },
                {
                        "type": "image",
                        "image_url": "%s",
                        "alt_text": "%s"
                }
        ]
}
EOT;
    $json = sprintf($json, $daydata["title"], $daydata["link"], $daydata["image"], $daydata["title"]);
    $exec = "curl -X POST -H 'Content-type: application/json' --data '" . $json . "' " . $webhook_url;
    exec($exec);
  }
}
