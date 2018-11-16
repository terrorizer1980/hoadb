/*==============================================================================
 * (C) Copyright 2015,2016,2017,2018 John J Kauflin, All rights reserved. 
 *----------------------------------------------------------------------------
 * DESCRIPTION: 
 *----------------------------------------------------------------------------
 * Modification History
 * 2015-03-06 JJK 	Initial version 
 * 2016-05-19 JJK   Modified to get the country web site URL's from config
 * 2016-06-05 JJK   Split Edit modal into 1 and 2Col versions
 * 2016-06-09 JJK	Added duesStatementNotes to the individual dues
 * 					statement and adjusted the format
 * 2016-06-24 JJK	Working on adminExecute (for yearly dues statement)
 * 2016-07-01 JJK	Got progress bar for adminExecute working by moving loop
 * 					processing into an asynchronous recursive function.
 * 2016-07-13 JJK   Finished intial version of yearly dues statements
 * 2016-07-14 JJK   Added Paid Dues Counts report
 * 2016-07-28 JJK	Corrected compound interest problem with a bad start date
 * 					Added print of LienComment after Total Due on Dues Statement
 * 2016-07-30 JJK   Changed the Yearly Dues Statues to just display prior
 * 					years due messages instead of amounts.
 * 					Added yearlyDuesStatementNotice for 2nd notice message.
 * 					Added DateDue to CSV for reports
 * 2016-08-19 JJK	Added UseMail to properties and EmailAddr to owners
 * 2016-08-20 JJK	Implemented email validation check
 * 2016-08-26 JJK   Went live, and Paypal payments working in Prod!!!
 * 2017-06-10 JJK   Added unpaid dues ranking
 * 2017-08-13 JJK	Added a dues email test function, and use of payment
 * 					email for dues statements
 * 2017-08-18 JJK   Added an unsubscribe message to the dues email
 * 2017-08-19 JJK   Added yearly dues statement notice and notes different
 * 					for 1st and Additional notices
 * 2017-08-20 JJK   Added Mark notice mailed function and finished up
 *                  Email logic.
 * 					Added logic to set NoticeDate
 * 2018-01-21 JJK	Corrected set of default firstNotice to false (so 2nd
 * 					notices would correctly use the alternate notes)
 * 2018-10-14 JJK   Re-factored for modules
 * 2018-11-03 JJK   Got update Properties working again with JSON POST
 * 2018-11-04 JJK   (Jackson's 16th birthday)
 *                  Got update Owner working again with JSON POST
 *                  Got update Assessment working again with JSON POST
 *============================================================================*/
var admin = (function () {
    'use strict';

    //=================================================================================================================
    // Private variables for the Module
    //var hoaRec;
    var commDesc = '';
    var noticeType = '';
    var tempCommDesc = "";
    var sendEmailAddr = "";
    var firstNotice = false;
    var adminRecCntMAX = 0;
    var sendEmail = true;
    var noticeDate = "";
    var noticeYear = "";
    // Global variable for loop counter
    var adminRecCnt = 0;
    var emailRecCnt = 0;
    var adminEmailSkipCnt = 0;
    // Global variable for total number of parcels in the HOA
    var hoaPropertyListMAX = 542;
    var hoaRecList = [];

    //=================================================================================================================
    // Variables cached from the DOM
    var $document = $(document);
    var $moduleDiv = $('#AdminPage');
    // Figure out a better way to do this
    var $displayPage = $document.find('#navbar a[href="#AdminPage"]');
    var $DuesAmt = $moduleDiv.find("#DuesAmt");
    var $FiscalYear = $moduleDiv.find("#FiscalYear");
    var $ConfirmationModal = $document.find("#ConfirmationModal");
    var $ConfirmationButton = $ConfirmationModal.find("#ConfirmationButton");
    var $ConfirmationMessage = $ConfirmationModal.find("#ConfirmationMessage");
    var $ResultMessage = $moduleDiv.find("#ResultMessage");
    
    //=================================================================================================================
    // Bind events
    $moduleDiv.on("click", ".AdminButton", _adminRequest);
    $ConfirmationButton.on("click", "#AdminExecute", _adminExecute);

    //=================================================================================================================
    // Module methods
    function _adminRequest(event) {
        // Validate add assessments (check access permissions, timing, year, and amount)
        // get confirmation message back
        var fy = util.cleanStr($FiscalYear.val());
        var duesAmt = util.cleanStr($DuesAmt.val());
        util.waitCursor();
        $.getJSON("adminValidate.php", "action=" + event.target.getAttribute('id') +
            "&fy=" + fy +
            "&duesAmt=" + duesAmt, function (adminRec) {
            $ConfirmationMessage.html(adminRec.message);
            $ConfirmationButton.empty();
            var buttonForm = $('<form>').prop('class', "form-inline").attr('role', "form");
            // If the action was Valid, append an action button
            if (adminRec.result == "Valid") {
                buttonForm.append($('<button>').prop('id', "AdminExecute").prop('class', "btn btn-danger").attr('type', "button").attr('data-dismiss', "modal").html('Continue')
                    .attr('data-action', event.target.getAttribute('id')).attr('data-fy', fy).attr('data-duesAmt', duesAmt));
            }
            buttonForm.append($('<button>').prop('class', "btn btn-default").attr('type', "button").attr('data-dismiss', "modal").html('Close'));
            $ConfirmationButton.append(buttonForm);
            util.defaultCursor();
            $ConfirmationModal.modal();
        });
    }

//MSR - molten salt reactor
// LFTR - lithium floride thorium reactor (a type of MSR)
// Tesla powerwall

    // Respond to the Continue click for an Admin Execute function 
    function _adminExecute(event) {
        $ResultMessage.html("Executing Admin request...");

        util.waitCursor();
        var action = event.target.getAttribute("data-action");
        //console.log("in adminExecute, action = "+action);

        $.getJSON("adminExecute.php", "action=" + action +
            "&fy=" + event.target.getAttribute("data-fy") +
            "&duesAmt=" + event.target.getAttribute("data-duesAmt"), function (adminRec) {
            util.defaultCursor();

            $ResultMessage.html(adminRec.message);

            if (action == 'DuesNotices' || action == 'DuesEmails' || action == 'DuesEmailsTest' || action == 'DuesRank' || action == 'MarkMailed') {
                var currSysDate = new Date();
                
                //pdfTitle = "Member Dues Notice";
                //pdfTimestamp = currSysDate.toString().substr(0, 24);

                // Reset the loop counter
                adminRecCnt = 0;
                adminEmailSkipCnt = 0;
                emailRecCnt = 0;

                hoaRecList = [];
                /*
                console.log("Before adminLoop, hoaPropertyRecList.length = " + adminRec.hoaPropertyRecList.length);

                $.each(adminRec.hoaPropertyRecList, function (index, hoaPropertyRec) {
                    console.log(index + ", parcelId = " + hoaPropertyRec.parcelId);
                    //$ResultMessage.html(index + ", parcelId = " + hoaPropertyRec.parcelId);

                    $.getJSON("getHoaDbData.php", "parcelId=" + hoaPropertyRec.parcelId, function (hoaRec) {
                        console.log(index + ", ParcelId = " + hoaRec.Parcel_ID + ", OwnerID = " + hoaRec.ownersList[0].OwnerID + ", Owner = " + hoaRec.ownersList[0].Owner_Name1 + ", hoaRec.DuesEmailAddr = " + hoaRec.DuesEmailAddr);
                        //$ResultMessage.html(index + ", parcelId = " + hoaPropertyRec.parcelId);
                        if (index >= adminRec.hoaPropertyRecList.length-1) {
                            $ResultMessage.html("Done with last one, index = "+index);
                        }
                    });

                });
                //$ResultMessage.html("Done with loop, cnt = " + adminRec.hoaPropertyRecList.length);
                console.log("Done with loop, cnt = " + adminRec.hoaPropertyRecList.length);
                */
                $ResultMessage.html("Done with loop - doing background processing...");

                // Start asynchronous recursive loop to process the list and create Yearly Dues Statment PDF's
                //setTimeout(adminLoop, 5, adminRec.hoaPropertyRecList, action);
            } // End of if

        }); // $.getJSON("adminExecute.php","action="+action+
    }

    //=================================================================================================================
    // This is what is exposed from this Module
    return {
        //getHoaRec: getHoaRec
    };

})(); // var admin = (function(){
