var menNames = [
  "Aaron",
  "Abraham",
  "Adam",
  "Adrian",
  "Aidan",
  "Alan",
  "Albert",
  "Alejandro",
  "Alex",
  "Alexander",
  "Alfred",
  "Andrew",
  "Angel",
  "Anthony",
  "Antonio",
  "Ashton",
  "Austin",
  "Benjamin",
  "Bernard",
  "Blake",
  "Brandon",
  "Brian",
  "Bruce",
  "Bryan",
  "Cameron",
  "Carl",
  "Carlos",
  "Charles",
  "Christopher",
  "Cole",
  "Connor",
  "Caleb",
  "Carter",
  "Chase",
  "Christian",
  "Clifford",
  "Cody",
  "Colin",
  "Curtis",
  "Cyrus",
  "Daniel",
  "David",
  "Dennis",
  "Devin",
  "Diego",
  "Dominic",
  "Donald",
  "Douglas",
  "Dylan",
  "Edward",
  "Elijah",
  "Eric",
  "Ethan",
  "Evan",
  "Francis",
  "Fred",
  "Gabriel",
  "Gavin",
  "Geoffrey",
  "George",
  "Gerld",
  "Gilbert",
  "Gordon",
  "Graham",
  "Gregory",
  "Harold",
  "Harry",
  "Hayden",
  "Henry",
  "Herbert",
  "Horace",
  "Howard",
  "Hugh",
  "Hunter",
  "Ian",
  "Isaac",
  "Isaiah",
  "Jack",
  "Jackson",
  "Jacob",
  "Jaden",
  "Jake",
  "James",
  "Jason",
  "Jayden",
  "Jeffery",
  "Jeremiah",
  "Jesse",
  "Jesus",
  "John",
  "Jonathan",
  "Jordan",
  "Jose",
  "Joseph",
  "Joshua",
  "Juan",
  "Julian",
  "Justin",
  "Keith",
  "Kevin",
  "Kyle",
  "Landon",
  "Lawrence",
  "Leonars",
  "Lewis",
  "Logan",
  "Louis",
  "Lucas",
  "Luke",
  "Malcolm",
  "Martin",
  "Mason",
  "Matthew",
  "Michael",
  "Miguel",
  "Miles",
  "Morgan",
  "Nathan",
  "Nathaniel",
  "Neil",
  "Nicholas",
  "Noah",
  "Norman",
  "Oliver",
  "Oscar",
  "Oswald",
  "Owen",
  "Patrick",
  "Peter",
  "Philip",
  "Ralph",
  "Raymond",
  "Reginald",
  "Richard",
  "Robert",
  "Rodrigo",
  "Roger",
  "Ronald",
  "Ryan",
  "Samuel",
  "Sean",
  "Sebastian",
  "Seth",
  "Simon",
  "Stanley",
  "Steven",
  "Thomas",
  "Timothy",
  "Tyler",
  "Wallace",
  "Walter",
  "William",
  "Wyatt",
  "Xavier",
  "Zachary",
];
var womenNames = [
  "Aaliyah",
  "Abigail",
  "Ada",
  "Adelina",
  "Agatha",
  "Alexa",
  "Alexandra",
  "Alexis",
  "Alise",
  "Allison",
  "Alyssa",
  "Amanda",
  "Amber",
  "Amelia",
  "Angelina",
  "Anita",
  "Ann",
  "Ariana",
  "Arianna",
  "Ashley",
  "Audrey",
  "Autumn",
  "Ava",
  "Avery",
  "Bailey",
  "Barbara",
  "Beatrice",
  "Belinda",
  "Brianna",
  "Bridjet",
  "Brooke",
  "Caroline",
  "Catherine",
  "Cecilia",
  "Celia",
  "Chloe",
  "Christine",
  "Claire",
  "Daisy",
  "Danielle",
  "Deborah",
  "Delia",
  "Destiny",
  "Diana",
  "Dorothy",
  "Eleanor",
  "Elizabeth",
  "Ella",
  "Emily",
  "Emma",
  "Erin",
  "Evelyn",
  "Faith",
  "Fiona",
  "Florence",
  "Freda",
  "Gloria",
  "Gabriella",
  "Gabrielle",
  "Gladys",
  "Grace",
  "Hailey",
  "Haley",
  "Hannah",
  "Helen",
  "Isabel",
  "Isabella",
  "Jacqueline",
  "Jada",
  "Jane",
  "Jasmine",
  "Jenna",
  "Jennifer",
  "Jessica",
  "Jocelyn",
  "Jordan",
  "Josephine",
  "Joyce",
  "Julia",
  "Kaitlyn",
  "Katelyn",
  "Katherine",
  "Kathryn",
  "Kayla",
  "Kaylee",
  "Kimberly",
  "Kylie",
  "Laura",
  "Lauren",
  "Leah",
  "Leonora",
  "Leslie",
  "Lillian",
  "Lily",
  "Linda",
  "Lorna",
  "Luccile",
  "Lucy",
  "Lynn",
  "Mabel",
  "Mackenzie",
  "Madeline",
  "Madison",
  "Makayla",
  "Margaret",
  "Maria",
  "Marisa",
  "Marjorie",
  "Mary",
  "Maya",
  "Megan",
  "Melanie",
  "Melissa",
  "Mia",
  "Michelle",
  "Mildred",
  "Molly",
  "Monica",
  "Nancy",
  "Natalie",
  "Nicole",
  "Nora",
  "Olivia",
  "Paige",
  "Pamela",
  "Patricia",
  "Pauline",
  "Penelope",
  "Priscilla",
  "Rachel",
  "Rebecca",
  "Riley",
  "Rita",
  "Rosalind",
  "Rose",
  "Samantha",
  "Sandra",
  "Sara",
  "Sarah",
  "Savannah",
  "Sharon",
  "Sheila",
  "Shirley",
  "Sierra",
  "Sofia",
  "Sophia",
  "Stephanie",
  "Susan",
  "Sybil",
  "Sydney",
  "Sylvia",
  "Taylor",
  "Trinity",
  "Vanessa",
  "Victoria",
  "Violet",
  "Virginia",
  "Winifred",
  "Yvonne",
  "Zoe",
];
var surnames = [
  "Abramson",
  "Adamson",
  "Adderiy",
  "Addington",
  "Adrian",
  "Albertson",
  "Aldridge",
  "Allford",
  "Alsopp",
  "Anderson",
  "Andrews",
  "Archibald",
  "Arnold",
  "Arthurs",
  "Atcheson",
  "Attwood",
  "Audley",
  "Austin",
  "Ayrton",
  "Babcock",
  "Backer",
  "Baldwin",
  "Bargeman",
  "Barnes",
  "Barrington",
  "Bawerman",
  "Becker",
  "Benson",
  "Berrington",
  "Birch",
  "Bishop",
  "Black",
  "Blare",
  "Blomfield",
  "Boolman",
  "Bootman",
  "Bosworth",
  "Bradberry",
  "Bradshaw",
  "Brickman",
  "Brooks",
  "Brown",
  "Bush",
  "Calhoun",
  "Campbell",
  "Carey",
  "Carrington",
  "Carroll",
  "Carter",
  "Chandter",
  "Chapman",
  "Charlson",
  "Chesterton",
  "Clapton",
  "Clifford",
  "Coleman",
  "Conors",
  "Cook",
  "Cramer",
  "Creighton",
  "Croftoon",
  "Crossman",
  "Daniels",
  "Davidson",
  "Day",
  "Dean",
  "Derrick",
  "Dickinson",
  "Dodson",
  "Donaldson",
  "Donovan",
  "Douglas",
  "Dowman",
  "Dutton",
  "Duncan",
  "Dunce",
  "Durham",
  "Dyson",
  "Eddington",
  "Edwards",
  "Ellington",
  "Elmers",
  "Enderson",
  "Erickson",
  "Evans",
  "Faber",
  "Fane",
  "Farmer",
  "Farrell",
  "Ferguson",
  "Finch",
  "Fisher",
  "Fitzgerald",
  "Flannagan",
  "Flatcher",
  "Fleming",
  "Ford",
  "Forman",
  "Forster",
  "Foster",
  "Francis",
  "Fraser",
  "Freeman",
  "Fulton",
  "Galbraith",
  "Gardner",
  "Garrison",
  "Gate",
  "Gerald",
  "Gibbs",
  "Gilbert",
  "Gill",
  "Gilmore",
  "Gilson",
  "Gimson",
  "Goldman",
  "Goodman",
  "Gustman",
  "Haig",
  "Hailey",
  "Hamphrey",
  "Hancock",
  "Hardman",
  "Harrison",
  "Hawkins",
  "Higgins",
  "Hodges",
  "Hoggarth",
  "Holiday",
  "Holmes",
  "Howard",
  "Jacobson",
  "James",
  "Jeff",
  "Jenkin",
  "Jerome",
  "Johnson",
  "Jones",
  "Keat",
  "Kelly",
  "Kendal",
  "Kennedy",
  "Kennett",
  "Kingsman",
  "Kirk",
  "Laird",
  "Lamberts",
  "Larkins",
  "Lawman",
  "Leapman",
  "Leman",
  "Lewin",
  "Little",
  "Livingston",
  "Longman",
  "MacAdam",
  "MacAlister",
  "MacDonald",
  "Macduff",
  "Macey",
  "Mackenzie",
  "Mansfield",
  "Marlow",
  "Marshman",
  "Mason",
  "Mathews",
  "Mercer",
  "Michaelson",
  "Miers",
  "Miller",
  "Miln",
  "Milton",
  "Molligan",
  "Morrison",
  "Murphy",
  "Nash",
  "Nathan",
  "Neal",
  "Nelson",
  "Nevill",
  "Nicholson",
  "Nyman",
  "Oakman",
  "Ogden",
  "Oldman",
  "Oldridge",
  "Oliver",
  "Osborne",
  "Oswald",
  "Otis",
  "Owen",
  "Page",
  "Palmer",
  "Parkinson",
  "Parson",
  "Pass",
  "Paterson",
  "Peacock",
  "Pearcy",
  "Peterson",
  "Philips",
  "Porter",
  "Quincy",
  "Raleigh",
  "Ralphs",
  "Ramacey",
  "Reynolds",
  "Richards",
  "Roberts",
  "Roger",
  "Russel",
  "Ryder",
  "Salisburry",
  "Salomon",
  "Samuels",
  "Saunder",
  "Shackley",
  "Sheldon",
  "Sherlock",
  "Shorter",
  "Simon",
  "Simpson",
  "Smith",
  "Stanley",
  "Stephen",
  "Stevenson",
  "Sykes",
  "Taft",
  "Taylor",
  "Thomson",
  "Thorndike",
  "Thornton",
  "Timmons",
  "Tracey",
  "Turner",
  "Vance",
  "Vaughan",
  "Wainwright",
  "Walkman",
  "Wallace",
  "Waller",
  "Walter",
  "Ward",
  "Warren",
  "Watson",
  "Wayne",
  "Webster",
  "Wesley",
  "White",
  "WifKinson",
  "Winter",
  "Wood",
  "Youmans",
  "Young",
];
var years = [
  30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48,
  49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63,
];
var prices = [
  150, 151, 152, 190, 191, 182, 193, 214, 215, 196, 197, 98, 159, 151, 152, 153, 154, 155, 156, 157, 158, 159, 160, 161, 162, 163, 164, 165, 166, 167, 168, 169, 170,
];
var states = [
  "ID",
  "IA",
  "AL",
  "AK",
  "AZ",
  "AR",
  "WY",
  "WA",
  "VT",
  "VA",
  "WI",
  "HI",
  "DE",
  "GA",
  "WV",
  "IL",
  "IN",
  "CA",
  "KS",
  "KY",
  "CO",
  "CT",
  "LA",
  "MA",
  "MN",
  "MS",
  "MO",
  "MI",
  "MT",
  "ME",
  "MD",
  "NE",
  "NV",
  "NH",
  "NJ",
  "NY",
  "NM",
  "OH",
  "OK",
  "OR",
  "PA",
  "RI",
  "ND",
  "NC",
  "TN",
  "TX",
  "FL",
  "SD",
  "SC",
  "UT",
];
let set_0 = false;
var arrayRandElement = function arrayRandElement(arr) {
  var rand = Math.floor(Math.random() * arr.length);
  return arr[rand];
};

var getQuoteRow = function getQuoteRow() {
  var name = arrayRandElement(menNames.concat(womenNames));
  var surname = arrayRandElement(surnames);
  var year = arrayRandElement(years);
  var state = arrayRandElement(states);
  var price = arrayRandElement(prices);
  var row =
    "<tr><td>" +
    name +
    " " +
    surname +
    ', <span class="nowrap">' +
    year +
    " y.o.</span></td><td>" +
    state +
    "</td><td>" +
    "$" +
    price +
    "/mo</td><tr>";
  $("table tbody").append(row);
  $(".table").animate({
    scrollTop: $("table tbody").height(),
  });
};

window.customer_data = {};

function step1(answer) {
  customer_data.age = answer;
  $("#step1").fadeOut(300, function () {
    $("#step2").fadeIn(300);
  });
}
function step2(answer) {
  customer_data.medicaid = answer;
  if (answer === true) {
    set_0 = true;
  }
  $("#step2").fadeOut(300, function () {
    $("#step3").fadeIn(300);
  });
}
function step3(answer) {
  customer_data.income = answer;
  if (answer === true && set_0 === true) {
    set_0 = true;
  } else {
    set_0 = false;
  }
  $("#step3").fadeOut(300, function () {
    step4();
  });
}
function step4() {
  $("#step4").fadeIn(300);
  setTimeout(function () {
    $("#step4").fadeOut(300, function () {
      step5();
    });
  }, 1500);
}
function step5() {
  $("#step5").fadeIn(300);
  setTimeout(function () {
    $("#step5").fadeOut(300, function () {
      step6();
    });
  }, 1500);
}
function step6() {
  $("#step6").fadeIn(300);
  setTimeout(function () {
    if (set_0 === true) {
      $("#step6").fadeOut(300, function () {
      step7();
    });
    }else{
       $("#step6").fadeOut(300, function () {
        $("#noqval").fadeIn(300);
      });
    }
    
  }, 1500);
}
function step7() {
  $("#step7").fadeIn(300);
  setTimeout(function () {
    if (set_0 === true) {
      $("#step7").fadeOut(300, function () {
        $("#finish").fadeIn(300);
        fbq("track", leadsubmission);
      });
    } else {
      $("#step7").fadeOut(300, function () {
        $("#noqval").fadeIn(300);
      });
    }
    // redirect_customer();
  }, 1000);
}
let redirect_customer = async () => {
  if (!customer_data.medicaid) {
    // redirect to traffic_back
    alert("Sorry, you are not qualify!");
    return;
  }
  if (!customer_data.age) {
    // redirect to traffic_back
    alert("Sorry, you are not qualify!");
    return;
  }
  if (!customer_data.income) {
    // redirect to traffic_back
    alert("Sorry, you are not qualify!");
    return;
  }
  location.href = "offer";
};

document.addEventListener("DOMContentLoaded", function () {
  var myFunction = function myFunction() {
    var timer = (Math.floor(Math.random() * 5) + 3) * 1000;
    getQuoteRow();
    setTimeout(myFunction, timer);
  };
  getQuoteRow();
  getQuoteRow();
  getQuoteRow();
  getQuoteRow();
  getQuoteRow();
  setTimeout(myFunction, 1500);
  $("button[data-smooth-scroll^='#']").click(function () {
    var _href = $(this).attr("data-smooth-scroll");
    $("html, body").animate({
      scrollTop: $(_href).offset().top - 500 + "px",
    });
    return false;
  });
});
